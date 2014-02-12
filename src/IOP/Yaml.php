<?php

namespace IOP;

// use \SplFileObject;
use \DirectoryIterator;
use \RecursiveDirectoryIterator;
use \RecursiveRegexIterator;
use \RecursiveIteratorIterator;
use \ArrayIterator;
use \RegexIterator;

class Yaml extends \Symfony\Component\Yaml\Yaml
{
    public static function parse($path, $exceptionOnInvalidType = false, $objectSupport = false)
    {
        try {
            $path = new \SplFileObject($path);
            $file = file_get_contents($path->getRealPath());
            $fileparts = preg_split('/^---\s*$/m', $file, 2, PREG_SPLIT_NO_EMPTY);
            try {
                $yaml = parent::parse($fileparts[0], $exceptionOnInvalidType, $objectSupport);
            } catch (\Exception $e) {
                $yaml = array('body' => $file);
            }

            if (isset($fileparts[1])) {
                if (isset($yaml['body'])) {
                    $yaml['body'] = rtrim($yaml['body']) . "\n\n" . $fileparts[1];
                } else {
                    $yaml['body'] = $fileparts[1];
                }
            } else {
                // TODO: does this ever get called?
                if (is_string($yaml)) {
                    $yaml = array('body' => $yaml);
                }
            }
            if (isset($yaml['body'])) {
                $yaml['body'] = \Michelf\MarkdownExtra::defaultTransform($yaml['body']);
                $yaml['body'] = \Michelf\SmartyPants::defaultTransform($yaml['body'], 3);
            }
            $boilerplate = array(
                'slug' => $path->getBasename('.'. $path->getExtension()),
                'yaml_source_file' => $path->getRealPath()
                );
            $yaml = array_merge($boilerplate, $yaml);
            $trim_strings = function (&$str) {
                $str = ((is_string($str))) ? trim($str) : $str;
            };
            array_walk_recursive($yaml, $trim_strings);
            return (is_array($yaml)) ? $yaml : array();
        } catch (\Exception $e) {
            if (get_class($path) == 'SplFileObject') {
                $path = $path->getFilename();
            }
            printf(ERROR_FORMAT, "An error occurred: Unable to parse '$path'\n");
            printf(ERROR_FORMAT, implode("\n\t", explode("\n", $e)) . "\n");
            return array('IOPYamlError' => $e);
        }
    }
}

