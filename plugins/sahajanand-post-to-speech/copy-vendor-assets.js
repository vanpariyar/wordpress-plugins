const fs = require('fs');
const path = require('path');

function copyFile(src, dest) {
	if (!fs.existsSync(src)) {
		console.warn(`Source file not found: ${src}`);
		return;
	}
	fs.mkdirSync(path.dirname(dest), { recursive: true });
	fs.copyFileSync(src, dest);
	console.log(`Copied ${src} to ${dest}`);
}

const pluginRoot = __dirname;

// 1. copy espeak-ng assets
const espeakSrcDir = path.join(pluginRoot, 'node_modules', 'espeak-ng', 'dist');
const espeakDestDir = path.join(pluginRoot, 'assets', 'vendor', 'espeak-ng');
copyFile(path.join(espeakSrcDir, 'espeak-ng.js'), path.join(espeakDestDir, 'espeak-ng.js'));
copyFile(path.join(espeakSrcDir, 'espeak-ng.wasm'), path.join(espeakDestDir, 'espeak-ng.wasm'));

// 2. copy onnxruntime-web assets
const onnxSrcDir = path.join(pluginRoot, 'node_modules', 'onnxruntime-web', 'dist');
const onnxDestDir = path.join(pluginRoot, 'assets', 'vendor', 'onnxruntime-web');

const onnxFiles = [
	'ort.min.js',
	'ort-wasm-simd-threaded.mjs',
	'ort-wasm-simd-threaded.wasm',
];

onnxFiles.forEach((file) => {
	copyFile(path.join(onnxSrcDir, file), path.join(onnxDestDir, file));
});
