import fs from 'fs/promises';
import path from 'path';

async function readModuleDirectories(modulesPath) {
  try {
    const entries = await fs.readdir(modulesPath, { withFileTypes: true });
    return entries
      .filter((entry) => entry.isDirectory() && entry.name !== '.DS_Store')
      .map((entry) => entry.name);
  } catch (error) {
    if (error.code === 'ENOENT') {
      return [];
    }

    throw error;
  }
}

async function writeModuleStatuses(moduleStatusesPath, moduleStatuses) {
  await fs.writeFile(
    moduleStatusesPath,
    `${JSON.stringify(moduleStatuses, null, 2)}\n`,
    'utf-8',
  );
}

async function loadModuleStatuses(moduleStatusesPath, moduleDirectories) {
  const defaultStatuses = Object.fromEntries(
    moduleDirectories.map((moduleDirectory) => [moduleDirectory, true]),
  );

  try {
    const moduleStatusesContent = await fs.readFile(moduleStatusesPath, 'utf-8');
    const parsedStatuses = JSON.parse(moduleStatusesContent);

    if (!parsedStatuses || typeof parsedStatuses !== 'object' || Array.isArray(parsedStatuses)) {
      throw new Error('modules_statuses.json must contain a JSON object.');
    }

    const mergedStatuses = { ...defaultStatuses, ...parsedStatuses };
    const missingModules = moduleDirectories.some((moduleDirectory) => !(moduleDirectory in parsedStatuses));

    if (missingModules) {
      await writeModuleStatuses(moduleStatusesPath, mergedStatuses);
    }

    return mergedStatuses;
  } catch (error) {
    const reason = error.code === 'ENOENT' ? 'not found' : `invalid (${error.message})`;
    console.warn(`modules_statuses.json ${reason}. Rebuilding defaults from installed modules.`);
    await writeModuleStatuses(moduleStatusesPath, defaultStatuses);
    return defaultStatuses;
  }
}

async function collectModuleAssetsPaths(paths, modulesPath) {
  modulesPath = path.join(__dirname, modulesPath);

  const moduleStatusesPath = path.join(__dirname, 'modules_statuses.json');

  try {
    const moduleDirectories = await readModuleDirectories(modulesPath);
    const moduleStatuses = await loadModuleStatuses(moduleStatusesPath, moduleDirectories);

    for (const moduleDir of moduleDirectories) {
      // Check if the module is enabled (status is true)
      if (moduleStatuses[moduleDir] === true) {
        const viteConfigPath = path.join(modulesPath, moduleDir, 'vite.config.js');
        try {
          const stat = await fs.stat(viteConfigPath);

          if (stat.isFile()) {
            // Import the module-specific Vite configuration
            const moduleConfig = await import(viteConfigPath);

            if (moduleConfig.paths && Array.isArray(moduleConfig.paths)) {
              paths.push(...moduleConfig.paths);
            }
          }
        } catch (error) {
          if (error.code !== 'ENOENT') {
            console.warn(`Unable to load Vite config for module ${moduleDir}: ${error.message}`);
          }
        }
      }
    }
  } catch (error) {
    console.error(`Error reading module statuses or module configurations: ${error.message}`);
  }

  return paths;
}

export default collectModuleAssetsPaths;
