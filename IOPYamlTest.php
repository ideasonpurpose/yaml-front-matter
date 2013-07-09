<?php

namespace Tests;

// fake the server location for error reporting
$_SERVER['HTTP_HOST'] = 'ideasonpurpose.com';

// Use custom Cache location since tests run as a different user
define('CACHE', false);
define('CACHE_DIR', '/tmp');

require_once __DIR__ . '/../functions.php';


// require_once __DIR__ . '/../lib/IOP/IOPYaml.php';
use IOP\IOPYaml;

class YamlTests extends \PHPUnit_Framework_TestCase
{
    protected $yaml_files;

    public function setUp()
    {
        // $this->yaml = new Yaml();
        $this->timing_iterations = 200;

        $one = Array (
            'title'=> 'YAML test file',
            'slug' => 'one',
            'array' => array('red', 'blue', 'green', 'yellow'),
            'yaml_source_file' => __DIR__ . '/yaml/tree/one.yaml');

        $two = Array (
            'title'=> 'YAML test file',
            'slug' => 'two',
            'array' => array('up', 'down', 'left', 'right'),
            'yaml_source_file' => __DIR__ . '/yaml/tree/two.yaml');

        $three = Array (
            'title'=> 'YAML test file 3',
            'slug' => 'three',
            'array' => array('cow', 'goat', 'pig', 'chicken'),
            'yaml_source_file' => __DIR__ . '/yaml/tree/subfolder/three.yaml');

        $frontmatter1 = Array (
            'slug' => 'frontmatter-one-delimiter',
            'yaml_source_file' =>  __DIR__ . 'yaml/frontmatter-one-delimiter.yaml',
            'title' => 'Testing YAML frontmatter',
            'body' => "The body text. This file only has a closing '---' delimiter before this body text.");

        $frontmatter2 = $frontmatter1;
        $frontmatter2['yaml_source_file'] = __DIR__ . 'yaml/frontmatter-two-delimiters.yaml';



        $this->yaml_files = (object) array(
            'one'=>(object) array('file'=>'/yaml/tree/one.yaml', 'content'=>$one),
            'two'=>(object) array('file'=>'/yaml/tree/two.yaml', 'content'=>$two),
            'three'=>(object) array('file'=>'/yaml/tree/subfolder/three.yaml', 'content'=>$three) );

        $this->yaml_tree = array(
            'one' => $this->yaml_files->one->content,
            'subfolder' => array(
                'three' => $this->yaml_files->three->content,
                'pages' => array($this->yaml_files->three->content)),
            'two' => $this->yaml_files->two->content,
            'pages' => array($this->yaml_files->one->content, $this->yaml_files->two->content));
    }


    public function testYamlObjectLoadMethod()
    {
        $yaml = $this->yaml_files->one;
        $expected = $yaml->content;
        $actual = IOPYaml::load(dirname(__FILE__) . $yaml->file);
        $this->assertEquals($expected, $actual);
    }

    public function testYamlObjectLoadMethodLooped()
    {
        $yaml = $this->yaml_files->one;
        for ($i=1; $i < $this->timing_iterations; $i++) {
            IOPYaml::load(__DIR__ . $yaml->file);
        }
        $this->assertTrue(true);
    }

    public function testLoadYamlAddsSlug()
    {
        $yaml = IOPYaml::load(dirname(__FILE__) . $this->yaml_files->one->file);
        $this->assertArrayHasKey('slug', $yaml);
    }

    public function testLoadFilesInPath()
    {
        $expected = array(
            'one' => $this->yaml_files->one->content,
            'two' => $this->yaml_files->two->content
        );
        $actual = IOPYaml::loadFilesInPath(__DIR__ . '/yaml/tree');
        $this->assertEquals($expected, $actual);
    }

    public function testLoadFilesInPathLooped()
    {
        for ($i=1; $i < $this->timing_iterations; $i++) {
            IOPYaml::loadFilesInPath(__DIR__ . '/yaml/tree');
        }
        $this->assertTrue(true);
    }

    // NOTE: Temporarily disabled while rebuilding the IOPYaml::loadTree() method
    // public function test_yaml_loadTree()
    // {
    //   $expected = $this->yaml_tree;
    //   $actual = IOPYaml::loadTree(__DIR__ . '/yaml/tree');
    //   $this->assertEquals($expected, $actual);
    // }

    public function testLoadFilesInNotAPath()
    {
        $this->assertEmpty(IOPYaml::loadFilesInPath(__DIR__ . 'not_a_path'));
    }

    public function testYamlFrontmatterOneDelimiter()
    {
        $actual = IOPYaml::parse(__DIR__ . '/yaml/frontmatter-one-delimiter.yaml');
        $this->assertEquals(4, count($actual));
    }

    public function testYamlFrontmatterTwoDelimiters()
    {
        $actual = IOPYaml::parse(__DIR__ . '/yaml/frontmatter-two-delimiters.yaml');
        $this->assertEquals(4, count($actual));
    }
}
