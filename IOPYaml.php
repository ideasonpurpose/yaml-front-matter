<?php

namespace IOP;

use \SplFileObject;
use \DirectoryIterator;
use \RecursiveDirectoryIterator;
use \RecursiveRegexIterator;
use \RecursiveIteratorIterator;
use \RegexIterator;
use Symfony\Component\Yaml\Yaml;
use dflydev\markdown\MarkdownExtraParser;
use Smartypants\Parser\SmartypantsParser;

class IOPYaml extends Yaml
{
    public static function parse($path, $exceptionOnInvalidType = false, $objectSupport = false)
    {
        try {
            $path = new SplFileObject($path);
            $file = file_get_contents($path->getRealPath());
            $markdownParser = new MarkdownExtraParser();
            $smartypants = new SmartypantsParser(array('smart_dashes'=> 3));
            $fileparts = preg_split('/\n*---\s*/', $file, 2, PREG_SPLIT_NO_EMPTY);
            $yaml = parent::parse($fileparts[0], $exceptionOnInvalidType, $objectSupport);
            $boilerplate = array(
                'slug' => $path->getBasename('.'. $path->getExtension()),
                'yaml_source_file' => $path->getRealPath()
                );
            $yaml = array_merge($boilerplate, $yaml);

            if (isset($fileparts[1])) {
                if (isset($yaml['body'])) {
                    $yaml['body'] = rtrim($yaml['body']) . "\n" . $fileparts[1];
                } else {
                    $yaml['body'] = $fileparts[1];
                }

            }
            if (isset($yaml['body'])) {
                $yaml['body'] = $markdownParser->transformMarkdown($yaml['body']);
                $yaml['body'] = $smartypants->transform($yaml['body']);
            }
            return (is_array($yaml)) ? $yaml : array();
        } catch (Exception $e) {
            if (get_class($path) == 'SplFileObject') {
                $path = $path->getFilename();
            }
            printf(ERROR_FORMAT, "An error occurred: Unable to parse '$path'\n");
            printf(ERROR_FORMAT, implode("\n\t", explode("\n", $e)) . "\n");
            return array();
        }
    }

    /**
     * placeholder for the cacheLoad method
     * Should be factored out?
     * @param  string $path Path to the YAML file
     * @return array       returns a parsed array
     */
    public static function load($path)
    {
        return self::cacheLoad($path);
    }


    /**
     * Load YAML files from cache, add new files to cache
     * @param  string $path Path to the YAML file
     * @return array       returns a parsed array
     */
    public static function cacheLoad($path)
    {
        // caching is enabled via the CACHE constant, defined at the top of functions.php
        if (CACHE) {
            // check if cache file exists and whether cache is newer than the source file
            $cachefile = CACHE_DIR . getRootRelativeUrl(realpath($path)) . '.json';
            if (file_exists($cachefile) && filemtime($cachefile) > filemtime($path)) {
                return json_decode(file_get_contents($cachefile), true);
            }
        }
        // if we're here, the cache was invalid
        $data = self::parse($path);

        if (CACHE) {
            if (!file_exists(dirname($cachefile))) {
                mkdir(dirname($cachefile), 0755, true);
            }
            $cached = ['cached'=> time(), 'cachefile'=> $cachefile];
            file_put_contents($cachefile, json_encode(array_merge($data, $cached)));
        }
        return $data;
    }

    /**
     * Loads all yaml files in $path
     * @param  string $path The path to search for YAML files
     * @return array       Returns either an array of parsed YAML or an empty array
     */
    public static function loadFilesInPath($path)
    {
        $files = array();
        $pattern = '/\.ya?ml/i';
        try {
            $dir = new DirectoryIterator($path);
            $Filter = new RegexIterator($dir, $pattern, RegexIterator::MATCH);
        } catch (\Exception $e) {
            // can't read the directory
            return array();
        }
        foreach ($Filter as $file) {
            try {
                $contents = self::load($file->getPathname());
                if ($contents) {
                    $files[] = $contents;
                }
            } catch (\Exception $e) {
                $e->setParsedFile(getRelativePath(WEB_ROOT, $file->getPathname()));
                $files['errors'][] = $e;
            }
        }
        return $files;
    }

    /**
     * Same as loadFilesInPath, but returns a flat list of all Yaml files in the tree
     */
    public static function loadFilesInPathRecursive($path)
    {
        $files = array();
        $pattern = '/\.ya?ml$/i';
        $Directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $NoDots = new RecursiveRegexIterator($Directory, '%^((?!/\.).)*$%');
        $Iterator = new RecursiveIteratorIterator($NoDots);
        $Filter = new RegexIterator($Iterator, $pattern);
        foreach ($Filter as $key => $file) {
            $contents = self::load($file->getPathname());
            if ($contents) {
                $files[] = $contents;
            }
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
        $Directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $Iterator = new RecursiveIteratorIterator($Directory);



        foreach ($Iterator as $file) {
            if (in_array(strtolower($file->getExtension()), array('yaml', 'yml'))) {
                // d($file->getPathname());
                continue;
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

