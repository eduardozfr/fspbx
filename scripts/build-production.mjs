import { spawn } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDirectory = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(scriptDirectory, '..');
const viteBinary = resolve(projectRoot, 'node_modules', 'vite', 'bin', 'vite.js');
const outputDirectory = resolve(projectRoot, 'storage', 'app', 'public', 'vite');
const aceSourceDirectory = resolve(projectRoot, 'node_modules', 'ace-builds', 'src-noconflict');
const aceTargetDirectory = resolve(projectRoot, 'public', 'vendor', 'ace');

function runViteBuild(target, emptyOutDir) {
    return new Promise((resolveBuild, rejectBuild) => {
        const child = spawn(
            process.execPath,
            [viteBinary, 'build'],
            {
                cwd: projectRoot,
                env: {
                    ...process.env,
                    FS_PBX_VITE_BUILD_TARGET: target,
                    FS_PBX_VITE_EMPTY_OUT_DIR: emptyOutDir ? '1' : '0',
                },
                stdio: 'inherit',
            },
        );

        child.on('exit', (code) => {
            if (code === 0) {
                resolveBuild();
                return;
            }

            rejectBuild(new Error(`Vite build failed for target "${target}" with exit code ${code}.`));
        });

        child.on('error', rejectBuild);
    });
}

function readManifest(fileName) {
    const filePath = resolve(outputDirectory, fileName);

    if (!existsSync(filePath)) {
        return {};
    }

    return JSON.parse(readFileSync(filePath, 'utf-8'));
}

function writeMergedManifest() {
    const classicManifestPath = resolve(outputDirectory, 'manifest-classic.json');
    const inertiaManifestPath = resolve(outputDirectory, 'manifest-inertia.json');
    const mergedManifestPath = resolve(outputDirectory, 'manifest.json');

    const mergedManifest = {
        ...readManifest('manifest-classic.json'),
        ...readManifest('manifest-inertia.json'),
    };

    writeFileSync(mergedManifestPath, `${JSON.stringify(mergedManifest, null, 2)}\n`, 'utf-8');

    if (existsSync(classicManifestPath)) {
        rmSync(classicManifestPath);
    }

    if (existsSync(inertiaManifestPath)) {
        rmSync(inertiaManifestPath);
    }
}

function publishAceAssets() {
    if (!existsSync(aceSourceDirectory)) {
        return;
    }

    mkdirSync(resolve(projectRoot, 'public', 'vendor'), { recursive: true });
    cpSync(aceSourceDirectory, aceTargetDirectory, {
        force: true,
        recursive: true,
    });
}

async function main() {
    const requestedTarget = String(process.argv[2] || process.env.FS_PBX_VITE_BUILD_TARGET || '').toLowerCase();

    publishAceAssets();

    if (requestedTarget === 'all') {
        await runViteBuild('all', true);
        return;
    }

    if (requestedTarget === 'classic' || requestedTarget === 'inertia') {
        await runViteBuild(requestedTarget, true);
        return;
    }

    await runViteBuild('classic', true);
    await runViteBuild('inertia', false);
    writeMergedManifest();
}

main().catch((error) => {
    console.error(error.message);
    process.exit(1);
});
