<?php
/**
 * PluginEntity class
 *
 * @package ShahariaAzam\WPRankChecker
 */

namespace ShahariaAzam\WPRankChecker;

/**
 * Class PluginEntity
 * @package ShahariaAzam\WPRankChecker
 */
class PluginEntity
{
    /**
     * @var string
     */
    private $slug;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $url;

    /**
     * @var float
     */
    private $rating;

    /**
     * @var string
     */
    private $summary;

    /**
     * @var string
     */
    private $author;

    /**
     * @return string
     */
    public function getSlug() :? string
    {
        return $this->slug;
    }

    /**
     * @param  string $slug
     * @return PluginEntity
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle() :? string
    {
        return $this->title;
    }

    /**
     * @param  string $title
     * @return PluginEntity
     */
    public function setTitle(string $title): PluginEntity
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param  string $url
     * @return PluginEntity
     */
    public function setUrl(string $url): PluginEntity
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getRating():? float
    {
        return $this->rating;
    }

    /**
     * @param  float $rating
     * @return PluginEntity
     */
    public function setRating(float $rating): PluginEntity
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSummary():? string
    {
        return $this->summary;
    }

    /**
     * @param  string $summary
     * @return PluginEntity
     */
    public function setSummary(string $summary): PluginEntity
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAuthor() :? string
    {
        return $this->author;
    }

    /**
     * @param  string $author
     * @return PluginEntity
     */
    public function setAuthor(string $author): PluginEntity
    {
        $this->author = $author;
        return $this;
    }
}
