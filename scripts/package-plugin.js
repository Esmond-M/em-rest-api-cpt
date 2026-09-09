const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

const root = process.cwd();
const buildDir = path.join(root, 'build');
const zipPath = path.join(buildDir, 'em-rest-api-cpt.zip');
const tempDir = path.join(root, '.package-staging');

const excludedNames = new Set([
  '.git',
  '.github',
  '.package-staging',
  'node_modules',
  'vendor',
  'tests',
  'scripts',
  'docs',
  'build',
  '.wp-env.json',
  '.phpunit.result.cache',
  'composer.json',
  'composer.lock',
  'phpunit.xml.dist',
  'package.json',
  'package-lock.json',
  'em-rest-api-cpt-portfolio-improvement-plan.md',
]);

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function removeDirIfExists(dir) {
  if (fs.existsSync(dir)) {
    fs.rmSync(dir, { recursive: true, force: true });
  }
}

function copyFileOrDir(source, dest) {
  const stat = fs.statSync(source);
  if (stat.isDirectory()) {
    fs.mkdirSync(dest, { recursive: true });
    for (const entry of fs.readdirSync(source, { withFileTypes: true })) {
      copyFileOrDir(path.join(source, entry.name), path.join(dest, entry.name));
    }
    return;
  }

  ensureDir(path.dirname(dest));
  fs.copyFileSync(source, dest);
}

function stagePluginFiles() {
  removeDirIfExists(tempDir);
  ensureDir(tempDir);

  for (const entry of fs.readdirSync(root, { withFileTypes: true })) {
    const name = entry.name;

    if (excludedNames.has(name)) {
      continue;
    }

    if (name === 'em-rest-api-cpt.php' || name === 'classes' || name === 'readme.md' || name === 'languages') {
      copyFileOrDir(path.join(root, name), path.join(tempDir, name));
    }
  }
}

function archivePlugin() {
  removeDirIfExists(buildDir);
  ensureDir(buildDir);

  const tempZip = path.join(buildDir, 'em-rest-api-cpt-temp.zip');
  removeDirIfExists(tempZip);

  const zipArgs = ['-r', '-q', tempZip, '.'];
  execFileSync('powershell', ['-NoProfile', '-Command', `Compress-Archive -Path '${tempDir}\*' -DestinationPath '${tempZip}' -Force`], {
    cwd: root,
    stdio: 'inherit',
  });

  fs.copyFileSync(tempZip, zipPath);
  fs.rmSync(tempZip, { force: true });
  removeDirIfExists(tempDir);
}

stagePluginFiles();
archivePlugin();
console.log(`Created plugin archive: ${zipPath}`);
