<?php

namespace IOP\Test;

// fake the server location for error reporting
$_SERVER['HTTP_HOST'] = 'ideasonpurpose.com';

// Use custom Cache location since tests run as a different user
define('CACHE', false);
define('CACHE_DIR', '/tmp');

// require_once __DIR__ . '/../functions.php';

use IOP\Yaml;

class YamlTests extends \PHPUnit_Framework_TestCase
{
    protected $yaml_files;

    public function setUp()
    {
        $this->timing_iterations = 200;

        $one = array (
            'title'=> 'YAML test file',
            'slug' => 'one',
            'array' => array('red', 'blue', 'green', 'yellow'),
            'yaml_source_file' => __DIR__ . '/yaml/tree/one.yaml');

        $two = array (
            'title'=> 'YAML test file',
            'slug' => 'two',
            'array' => array('up', 'down', 'left', 'right'),
            'yaml_source_file' => __DIR__ . '/yaml/tree/two.yaml');

        $three = array (
            'title'=> 'YAML test file 3',
            'slug' => 'three',
            'array' => array('cow', 'goat', 'pig', 'chicken'),
            'yaml_source_file' => __DIR__ . '/yaml/tree/subfolder/three.yaml');

        $frontmatter1 = array (
            'slug' => 'frontmatter-one-delimiter',
            'yaml_source_file' =>  __DIR__ . 'yaml/frontmatter-one-delimiter.yaml',
            'title' => 'Testing YAML frontmatter',
            'body' => "The body text. This file only has a closing '---' delimiter before this body text.");

        $frontmatter2 = $frontmatter1;
        $frontmatter2['yaml_source_file'] = __DIR__ . 'yaml/frontmatter-two-delimiters.yaml';

        $frontmatter_empty = array (
            'slug' => 'frontmatter-empty-body',
            'yaml_source_file' => __DIR__ . 'yaml/frontmatter-empty-body.yaml',
            'title' => "There is no text after the '---' delimiter");

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

        // Use var_export() for paste-friendly dumps of parsed yaml files:
        $this->with_index = array ('array' => array (0 => 'item', ), 'files' => array ('filler' => array ('slug' => 'filler', 'yaml_source_file' => '/Users/joe/Sites/iop/app/Tests/yaml/with_index/filler.yaml', 'title' => 'YAML filler', 'array' => array (0 => 'lard', ), ), ), 'slug' => 'with_index', 'title' => 'With Index Test File', 'yaml_source_file' => '/Users/joe/Sites/iop/app/Tests/yaml/with_index/with_index.yaml', );
        $this->index_only = array ('array' => array (0 => 'item', ), 'files' => array (), 'slug' => 'index_only', 'title' => 'Index Only Test File', 'yaml_source_file' => '/Users/joe/Sites/iop/app/Tests/yaml/index_only/index_only.yaml', );
    }


    public function testYamlObjectLoadMethod()
    {
        $yaml = $this->yaml_files->one;
        $expected = $yaml->content;
        $actual = Yaml::parse(dirname(__FILE__) . $yaml->file);
        $this->assertEquals($expected, $actual);
    }

    public function testLoadYamlAddsSlug()
    {
        $yaml = Yaml::parse(dirname(__FILE__) . $this->yaml_files->one->file);
        $this->assertArrayHasKey('slug', $yaml);
    }

    // public function testLoadFilesInPath()
    // {
    //     $expected = array(
    //         'one' => $this->yaml_files->one->content,
    //         'two' => $this->yaml_files->two->content
    //     );
    //     $actual = IOPYaml::loadFilesInPath(__DIR__ . '/yaml/tree');
    //     $this->assertEquals($expected, $actual);
    // }

    // public function testLoadFilesInPathWithIndex()
    // {
    //     $expected = $this->with_index;
    //     $actual = IOPYaml::loadFilesInPath(__DIR__ . '/yaml/with_index');
    //     $this->assertEquals($expected, $actual);
    // }

    // public function testLoadFilesInPathIndexOnly()
    // {
    //     $expected = $this->index_only;
    //     $actual = IOPYaml::loadFilesInPath(__DIR__ . '/yaml/index_only');
    //     $this->assertEquals($expected, $actual);
    // }

    // public function testLoadFilesInNotAPath()
    // {
    //     $this->assertEmpty(IOPYaml::loadFilesInPath(__DIR__ . 'not_a_path'));
    // }

    public function testYamlFrontmatterOneDelimiter()
    {
        $actual = Yaml::parse(__DIR__ . '/yaml/frontmatter-one-delimiter.yaml');
        // 4 implies the array contains 4 keys; slug, yaml_source_file, title, and body
        $this->assertEquals(4, count($actual));
        $this->assertEquals('Testing YAML frontmatter', $actual['title']);
    }

    public function testYamlFrontmatterTwoDelimiters()
    {
        $actual = Yaml::parse(__DIR__ . '/yaml/frontmatter-two-delimiters.yaml');
        $this->assertEquals(4, count($actual));
    }

    public function testEmptyFrontMatter()
    {
        $expected = '<p>The frontmatter is <em>empty</em>.</p>';
        $actual = Yaml::parse(__DIR__ . '/yaml/markdown_passthrough/empty_frontmatter.yaml');
        $this->assertEquals($expected, $actual['body']);
    }

    public function testNoFrontMatter()
    {
        $expected = '<p>This is just <strong>Markdown</strong></p>';
        $actual = Yaml::parse(__DIR__ . '/yaml/markdown_passthrough/no_frontmatter.md');
        $this->assertEquals($expected, $actual['body']);
    }

    public function testTwoBodies()
    {
        $expected = "<p>One</p>\n\n<p>Two</p>";
        $actual = Yaml::parse(__DIR__ . '/yaml/two_bodies.yaml');
        $this->assertEquals($expected, $actual['body']);
    }

    public function testEmptyBody()
    {
        $actual = Yaml::parse(__DIR__ . '/yaml/frontmatter-empty-body.yaml');
        $this->assertArrayNotHasKey('body', $actual);
    }
}
