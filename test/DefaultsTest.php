<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DefaultsTest extends TestCase
{
    public $file = 'out.xml';

    public function setUp(): void
    {
        is_dir('./testdir') && rmrf('./testdir');
        mkdir('./testdir');
        copy('../dir2cast.php', './testdir/dir2cast.php');
        chdir('./testdir');
    }

    public function test_default_empty_podcast(): void
    {
        $output = array();
        $returncode = false;
        exec('php dir2cast.php --output=out.xml', $output, $returncode);
        $this->assertTrue(file_exists($this->file));

        $content = file_get_contents($this->file);
        $this->assertTrue(strlen($content) > 0);

        // warns the podcast is empty
        $this->assertSame(
            'Writing RSS to: out.xml\n** Warning: generated podcast found no episodes.',
            implode('\n', $output)
        );
        $this->assertSame(255, $returncode);

        // caches the output in the default temp folder
        $this->assertTrue(is_dir('./temp'));
        $cached_output_files = glob('./temp/*.xml');
        $this->assertSame(1, sizeof($cached_output_files));

        // caches what was generated
        $this->assertSame(
            $content,
            file_get_contents($cached_output_files[0])
        );

        // generated valid XML
        $data = simplexml_load_string($content);

        $this->assertEquals('testdir', $data->channel->title);
        $this->assertEquals('http://www.example.com/', $data->channel->link);
        $this->assertEquals('Podcast', $data->channel->description);
        $this->assertEquals('en-us', $data->channel->language);
        $this->assertEquals('60', $data->channel->ttl);

        $atom_elements = $data->channel->children("http://www.w3.org/2005/Atom");
        $this->assertEquals('http://www.example.com/rss', $atom_elements->link->attributes()['href']);
        $this->assertEquals('self', $atom_elements->link->attributes()['rel']);
        $this->assertEquals('application/rss+xml', $atom_elements->link->attributes()['type']);

        $itunes_elements = $data->channel->children("http://www.itunes.com/dtds/podcast-1.0.dtd");
        $this->assertEquals('Podcast', $itunes_elements->subtitle);
        $this->assertEquals('Podcast', $itunes_elements->summary);
        $this->assertEquals('', $itunes_elements->author);

    }

    public function tearDown(): void
    {
        chdir('..');
        // rmrf('./testdir');
    }

}
