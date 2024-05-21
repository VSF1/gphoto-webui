<?php
function getDirContents($path) {
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    	$files = array_filter(iterator_to_array($iterator), fn($file) => $file->isFile());
    	return $files;
}

function getDirContents_p($path) {
	$directory = new RecursiveDirectoryIterator($path, FilesystemIterator::FOLLOW_SYMLINKS);
	$filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
		// Skip hidden files and directories.
		if ($current->getFilename()[0] === '.') {
			return FALSE;
		} else if ($current->isDir()) {
			// Only recurse into intended subdirectories.
			# return $current->getFilename() === 'wanted_dirname';
			return TRUE;
		} else if ($current->isFile()) {
			return TRUE;
		} else {
			// Only consume files of interest.
			#return strpos($current->getFilename(), 'wanted_filename') === 0;
			return FALSE;
		}
	});

	$iterator = new \RecursiveIteratorIterator($filter);
	$files = array();
	foreach ($iterator as $info) {
		$files[] = $info->getPathname();
	}
	return $files;
}

$files = getDirContents_p('/srv/shifucc');
#var_dump($files);
echo "<br/>";
foreach ($files as $file) {
#	var_dump($file);
#	echo "<br/>";
	echo "<li>Path: ".$file."</li>";
}
?>
