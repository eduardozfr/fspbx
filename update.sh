#!/bin/bash

# Function to print success message
print_success() {
    echo -e "\e[32m$1 \e[0m"  # Green text
}

# Function to print error message
print_error() {
    echo -e "\e[31m$1 \e[0m"  # Red text
}

ensure_module_statuses_file() {
    local status_file="modules_statuses.json"

    if [ -f "$status_file" ]; then
        return 0
    fi

    if [ ! -d "Modules" ]; then
        printf "{}\n" > "$status_file"
        return 0
    fi

    local first=1
    printf "{\n" > "$status_file"

    for module_dir in Modules/*; do
        [ -d "$module_dir" ] || continue

        if [ $first -eq 0 ]; then
            printf ",\n" >> "$status_file"
        fi

        printf "  \"%s\": true" "$(basename "$module_dir")" >> "$status_file"
        first=0
    done

    printf "\n}\n" >> "$status_file"
}

# Run Composer Install
composer install
if [ $? -eq 0 ]; then
    print_success "Composer install completed successfully."
else
    print_error "Error occurred during 'composer install'."
    exit 1
fi

# Run Composer Dump-Autoload
composer dump-autoload
if [ $? -eq 0 ]; then
    print_success "Composer dump-autoload completed successfully."
else
    print_error "Error occurred during 'composer dump-autoload'."
    exit 1
fi

# Run NPM Install
ensure_module_statuses_file
if [ -z "${NODE_OPTIONS:-}" ]; then
    export NODE_OPTIONS="--max-old-space-size=${FS_PBX_NODE_BUILD_MEMORY:-2048}"
fi

npm install --no-audit --no-fund
if [ $? -eq 0 ]; then
    print_success "NPM install completed successfully."
else
    print_error "Error occurred during 'npm install'."
    exit 1
fi

# Run NPM Run Build
npm run build
if [ $? -eq 0 ]; then
    print_success "NPM run build completed successfully."
else
    print_error "Error occurred during 'npm run build'."
    exit 1
fi

echo "All build tasks completed successfully!"
