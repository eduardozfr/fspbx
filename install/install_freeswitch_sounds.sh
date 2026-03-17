#!/bin/bash

# Set error handling
set -euo pipefail

# Function to print success messages
print_success() {
    echo -e "\e[32m$1 \e[0m"
}

# Function to print error messages
print_error() {
    echo -e "\e[31m$1 \e[0m"
}

print_warn() {
    echo -e "\e[33m$1 \e[0m"
}

PT_BR_SOUND_VOICE="${FS_PBX_FREESWITCH_SOUND_VOICE:-karina}"
PT_BR_SOUND_LANGUAGE="${FS_PBX_FREESWITCH_SOUND_LANGUAGE:-pt}"
PT_BR_SOUND_DIALECT="${FS_PBX_FREESWITCH_SOUND_DIALECT:-BR}"
PT_BR_SOUND_VERSION="${FS_PBX_FREESWITCH_SOUND_VERSION:-1.0.51}"
RESOLVED_PTBR_SOUND_VERSION=""
RESOLVED_PTBR_ARCHIVE_DIALECT=""

build_unique_list() {
    local item
    local seen=" "

    for item in "$@"; do
        if [ -n "$item" ] && [[ "$seen" != *" $item "* ]]; then
            printf '%s\n' "$item"
            seen="${seen}${item} "
        fi
    done
}

mapfile -t PT_BR_SOUND_ARCHIVE_DIALECTS < <(
    build_unique_list \
        "${FS_PBX_FREESWITCH_SOUND_ARCHIVE_DIALECT:-}" \
        "${PT_BR_SOUND_DIALECT}" \
        "$(printf '%s' "$PT_BR_SOUND_DIALECT" | tr '[:upper:]' '[:lower:]')" \
        "$(printf '%s' "$PT_BR_SOUND_DIALECT" | tr '[:lower:]' '[:upper:]')"
)

mapfile -t PT_BR_SOUND_REQUIRED_RATES < <(
    build_unique_list \
        8000 \
        ${FS_PBX_FREESWITCH_SOUND_REQUIRED_RATES:-}
)

mapfile -t PT_BR_SOUND_OPTIONAL_RATES < <(
    build_unique_list \
        16000 \
        32000 \
        48000 \
        ${FS_PBX_FREESWITCH_SOUND_OPTIONAL_RATES:-}
)

mapfile -t PT_BR_SOUND_VERSION_CANDIDATES < <(
    build_unique_list \
        "${PT_BR_SOUND_VERSION}" \
        ${FS_PBX_FREESWITCH_SOUND_VERSION_CANDIDATES:-} \
        1.0.51 \
        1.0.52 \
        1.0.53
)

print_success "Installing FreeSWITCH Sounds..."

# Change working directory to FreeSWITCH source
cd /usr/src/freeswitch

install_ptbr_sound_archive() {
    local sample_rate="$1"
    local version="$2"
    local archive_dialect="$3"
    local archive_name="freeswitch-sounds-${PT_BR_SOUND_LANGUAGE}-${archive_dialect}-${PT_BR_SOUND_VOICE}-${sample_rate}-${version}.tar.gz"

    if [ ! -x "/usr/src/freeswitch/build/getsounds.sh" ]; then
        print_error "FreeSWITCH getsounds helper was not found at /usr/src/freeswitch/build/getsounds.sh."
        return 1
    fi

    print_success "Fetching ${archive_name}..."
    /usr/src/freeswitch/build/getsounds.sh "$archive_name" /usr/share/freeswitch/sounds/
}

install_required_ptbr_sound_set() {
    local version
    local archive_dialect
    local sample_rate

    for version in "${PT_BR_SOUND_VERSION_CANDIDATES[@]}"; do
        for archive_dialect in "${PT_BR_SOUND_ARCHIVE_DIALECTS[@]}"; do
            print_success "Trying PT-BR sound package version ${version} with archive dialect ${archive_dialect}..."

            local failed=0
            for sample_rate in "${PT_BR_SOUND_REQUIRED_RATES[@]}"; do
                if ! install_ptbr_sound_archive "$sample_rate" "$version" "$archive_dialect"; then
                    failed=1
                    break
                fi
            fi

            if [ "$failed" -eq 0 ]; then
                RESOLVED_PTBR_SOUND_VERSION="$version"
                RESOLVED_PTBR_ARCHIVE_DIALECT="$archive_dialect"
                print_success "PT-BR prompt set installed successfully with version ${version} (${archive_dialect})."
                return 0
            fi

            print_warn "PT-BR sound package version ${version} with archive dialect ${archive_dialect} could not be installed. Trying the next candidate..."
        done
    done

    return 1
}

install_optional_ptbr_sound_rates() {
    local sample_rate

    if [ -z "$RESOLVED_PTBR_SOUND_VERSION" ] || [ -z "$RESOLVED_PTBR_ARCHIVE_DIALECT" ]; then
        return 0
    fi

    for sample_rate in "${PT_BR_SOUND_OPTIONAL_RATES[@]}"; do
        if install_ptbr_sound_archive "$sample_rate" "$RESOLVED_PTBR_SOUND_VERSION" "$RESOLVED_PTBR_ARCHIVE_DIALECT"; then
            print_success "Installed optional PT-BR sound rate ${sample_rate}."
        else
            print_warn "Optional PT-BR sound rate ${sample_rate} is not available on this mirror. Continuing with the base prompt set."
        fi
    done
}

detect_installed_ptbr_dialect() {
    local candidate
    local detected

    for candidate in "${PT_BR_SOUND_ARCHIVE_DIALECTS[@]}"; do
        if [ -d "/usr/share/freeswitch/sounds/${PT_BR_SOUND_LANGUAGE}/${candidate}/${PT_BR_SOUND_VOICE}" ]; then
            printf '%s\n' "$candidate"
            return 0
        fi
    done

    detected=$(find "/usr/share/freeswitch/sounds/${PT_BR_SOUND_LANGUAGE}" \
        -mindepth 2 -maxdepth 2 -type d -name "${PT_BR_SOUND_VOICE}" -print -quit 2>/dev/null | awk -F/ '{print $(NF-1)}')

    if [ -n "$detected" ]; then
        printf '%s\n' "$detected"
        return 0
    fi

    return 1
}

configure_ptbr_defaults() {
    local vars_file="/etc/freeswitch/vars.xml"
    local detected_dialect

    if [ ! -f "$vars_file" ]; then
        print_warn "vars.xml not found at $vars_file. Skipping FreeSWITCH default language rewrite."
        return 0
    fi

    if detected_dialect=$(detect_installed_ptbr_dialect); then
        PT_BR_SOUND_DIALECT="$detected_dialect"
        print_success "Detected installed PT-BR sound dialect path: ${PT_BR_SOUND_DIALECT}"
    else
        print_warn "Could not auto-detect the installed PT-BR dialect path. Keeping configured dialect ${PT_BR_SOUND_DIALECT}."
    fi

    sed -i -E \
        -e "s/(default_language=)[^\"]+/\1${PT_BR_SOUND_LANGUAGE}/" \
        -e "s/(default_dialect=)[^\"]+/\1${PT_BR_SOUND_DIALECT}/" \
        -e "s/(default_voice=)[^\"]+/\1${PT_BR_SOUND_VOICE}/" \
        "$vars_file"
}

print_success "Downloading and installing PT-BR FreeSWITCH prompt set..."
install_required_ptbr_sound_set || {
    print_error "Unable to install the PT-BR FreeSWITCH prompt set from the available package candidates."
    exit 1
}
install_optional_ptbr_sound_rates

print_success "Downloading and installing default FreeSWITCH music on hold..."
make moh-install
make hd-moh-install
make cd-moh-install

# Ensure music directory exists before moving files
mkdir -p /usr/share/freeswitch/sounds/music/default

# Move music files into the correct directory
if mv /usr/share/freeswitch/sounds/music/*000 /usr/share/freeswitch/sounds/music/default/ 2>/dev/null; then
    print_success "Music files moved to /usr/share/freeswitch/sounds/music/default successfully."
else
    print_error "No music files found to move. This may not be an error."
fi

configure_ptbr_defaults

print_success "FreeSWITCH sounds installation completed!"
