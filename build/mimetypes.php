<?php

// Fetch all the aliases
$aliases = json_decode(file_get_contents(dirname(__DIR__) . '/config/mimetypealiases.json'), true);

// Fetch all files
$dir = new DirectoryIterator(dirname(__DIR__) . '/core/img/filetypes');

$files = [];
foreach($dir as $fileInfo) {
	if ($fileInfo->isFile()) {
		$file = preg_replace('/.[^.]*$/', '', $fileInfo->getFilename());
		$files[] = $file;
	}
}

//Remove duplicates
$files = array_values(array_unique($files));

//Generate the JS
$js = 'OC.MimeTypes={
	aliases: ' . json_encode($aliases, JSON_PRETTY_PRINT) . ',
	files: ' . json_encode($files, JSON_PRETTY_PRINT) . '
};
';

//Output the JS
file_put_contents(dirname(__DIR__) . '/core/js/mimetypes.js', $js);

