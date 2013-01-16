<?php

namespace IOP;

use \SplFileObject;
use \DirectoryIterator;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \RegexIterator;
use Symfony\Component\Yaml\Yaml;

class IOPYaml extends Yaml
{
    public static function parse($path)
    {
        try {
            $path = new SplFileObject($path);
            $file = file_get_contents($path);
            $fileparts = preg_split('/\n*---\s*/', $file, 2, PREG_SPLIT_NO_EMPTY);
            $yaml = parent::parse($fileparts[0]);
            $boilerplate = array(
                'slug' => $path->getBasename('.'. $path->getExtension()),
                'yaml_source_file' => $path->getRealPath()
                );
            $yaml = array_merge($boilerplate, $yaml);

            if (isset($fileparts[1])) {
                if (!isset($yaml['body'])) {
                    $yaml['body'] ='';
                }
                $yaml['body'] .= "\n" . $fileparts[1];
            }

            return (is_array($yaml)) ? $yaml : array();
        }
        catch (Exception $e) {
            d(get_class($path));
            if (get_class($path) == 'SplFileObject')
                $path = $path->getFilename();
            printf(ERROR_FORMAT, "An error occurred: Unable to parse '$path'\n");
            printf(ERROR_FORMAT,  implode("\n\t", explode("\n", $e)) . "\n");
            return array();
        }
    }

    /**
     * placeholder until the load method is factored out
     */
    public static function load($path)
    {
        return self::parse($path);
    }

    public static function loadFilesInPath($path)
    {
        $files = array();
        $pattern = '/\.ya?ml/i';
        $dir = new DirectoryIterator($path);
        $Filter = new RegexIterator($dir, $pattern, RegexIterator::MATCH);
        foreach ($Filter as $file) {
            $contents = self::load($file->getPathname());
            if ($contents) $files[] = $contents;
        }
        return $files;
    }

    /**
     * Same as loadFilesInPath, but returns a flat list of all Yaml files in the tree
     */
    public static function loadFilesInPathRecursive($path)
    {
        $files = array();
        $pattern = '/\.ya?ml/i';
        $Directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $Iterator = new RecursiveIteratorIterator($Directory);
        $Filter = new RegexIterator($Iterator, $pattern, RegexIterator::MATCH);
        foreach ($Filter as $file) {
            $contents = self::load($file->getPathname());
            if ($contents) $files[] = $contents;
        }
        return $files;

    }

    /**
     * Loads a filesystem tree of parsed (yaml|yml) files.
     * Each file is added with both a numerical and slug-based index.
     *
     */
    public static function loadTree($path)
    {
        $tree = array();
        $Directory = new RecursiveDirectoryIterator($path);
        $Iterator = new RecursiveIteratorIterator($Directory);

        foreach ($Iterator as $file) {
            if (in_array(strtolower($file->getExtension()), array('yaml', 'yml'))) {
                $flat[] = $file->getPathname();
                $path_parts = preg_split('#/#', str_replace($path, '', $file->getPath()), NULL, PREG_SPLIT_NO_EMPTY);

                // use references to point to a specific branch of the tree based on split path elements
                $nest = &$tree;
                foreach ($path_parts as $subpath) {
                    if (!isset($nest[$subpath]))
                        $nest[$subpath] = array();
                    $nest = &$nest[$subpath];
                }
                // krumo($nest, $nest_parent);
                $yaml =  self::load($file->getRealPath());
                if ($yaml['slug'] == 'index' || $yaml['slug'] == end($path_parts)) {
                    // merge index.yaml files up with the parent branch, they represent the content of the directory entry
                    // this is confusing-looking, but it's assigning a value to a *referenced index*
                    // of the tree array. $nest is a pointer to $tree['blah'], which is what gets merged
                    // these override same-named yaml siblings of the directory
                    // if there is a blah/blah.yaml and blah/index.yaml priority is determined alphabetically by filename (DON'T DO THIS)
                    $nest = array_merge($nest, $yaml);
                } elseif (isset($nest[$yaml['slug']]) && is_array($nest[$yaml['slug']])) {
                    // merge name.yaml into a sibling with a matching basename
                    $nest[$yaml['slug']] = array_merge($yaml, $nest[$yaml['slug']]);
                } else {
                    $nest[$yaml['slug']] = $yaml;
                }
                if (isset($nest[$yaml['slug']]))
                    $nest['pages'][] = &$nest[$yaml['slug']];
            }
        }
        // var_dump($flat);
        // var_dump($tree);
        return $tree;
    }
}