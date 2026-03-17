#!/bin/bash

# Set error handling
set -e

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
PT_BR_SOUND_DIALECT="${FS_PBX_FREESWITCH_SOUND_DIALECT:-br}"
PT_BR_SOUND_VERSION="${FS_PBX_FREESWITCH_SOUND_VERSION:-}"
PT_BR_SOUND_RATES=(8000 16000 32000 48000)
PT_BR_SOUND_VERSION_CANDIDATES=("1.0.53" "1.0.52" "1.0.51")

print_success "Installing FreeSWITCH Sounds..."

# Change working directory to FreeSWITCH source
cd /usr/src/freeswitch

resolve_ptbr_sound_version() {
    if [ -n "$PT_BR_SOUND_VERSION" ]; then
        echo "$PT_BR_SOUND_VERSION"
        return 0
    fi

    for candidate in "${PT_BR_SOUND_VERSION_CANDIDATES[@]}"; do
        local archive_url="https://files.freeswitch.org/releases/sounds/freeswitch-sounds-${PT_BR_SOUND_LANGUAGE}-${PT_BR_SOUND_DIALECT}-${PT_BR_SOUND_VOICE}-8000-${candidate}.tar.gz"

        if curl -fsI "$archive_url" >/dev/null 2>&1; then
            echo "$candidate"
            return 0
        fi
    done

    return 1
}

install_ptbr_sound_archive() {
    local sample_rate="$1"
    local version="$2"
    local archive_name="freeswitch-sounds-${PT_BR_SOUND_LANGUAGE}-${PT_BR_SOUND_DIALECT}-${PT_BR_SOUND_VOICE}-${sample_rate}-${version}.tar.gz"

    if [ ! -x "/usr/src/freeswitch/build/getsounds.sh" ]; then
        print_error "FreeSWITCH getsounds helper was not found at /usr/src/freeswitch/build/getsounds.sh."
        return 1
    fi

    /usr/src/freeswitch/build/getsounds.sh "$archive_name" /usr/share/freeswitch/sounds/
}

configure_ptbr_defaults() {
    local vars_file="/etc/freeswitch/vars.xml"

    if [ ! -f "$vars_file" ]; then
        print_warn "vars.xml not found at $vars_file. Skipping FreeSWITCH default language rewrite."
        return 0
    fi

    sed -i -E \
        -e "s/(default_language=)[^\"]+/\1${PT_BR_SOUND_LANGUAGE}/" \
        -e "s/(default_dialect=)[^\"]+/\1${PT_BR_SOUND_DIALECT}/" \
        -e "s/(default_voice=)[^\"]+/\1${PT_BR_SOUND_VOICE}/" \
        "$vars_file"
}

print_success "Downloading and installing PT-BR FreeSWITCH prompt set..."
PT_BR_SOUND_VERSION="$(resolve_ptbr_sound_version)" || {
    print_error "Unable to resolve a valid PT-BR FreeSWITCH sound package version."
    exit 1
}

for sample_rate in "${PT_BR_SOUND_RATES[@]}"; do
    install_ptbr_sound_archive "$sample_rate" "$PT_BR_SOUND_VERSION"
done

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
