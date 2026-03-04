<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;

class DocsController extends Controller
{
    private string $docsPath;
    private MarkdownConverter $markdown;

    public function __construct()
    {
        $this->docsPath = base_path('docs');

        $environment = new Environment([
            'heading_permalink' => [
                'html_class'  => 'anchor-link',
                'id_prefix'   => '',
                'insert'      => 'before',
                'symbol'      => '#',
                'title'       => 'Permalink',
                'aria_hidden' => true,
            ],
            'table_of_contents' => [
                'html_class'        => 'doc-toc',
                'position'          => 'placeholder',
                'placeholder'       => '[TOC]',
                'style'             => 'bullet',
                'min_heading_level' => 2,
                'max_heading_level' => 3,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new TableOfContentsExtension());

        $this->markdown = new MarkdownConverter($environment);
    }

    public function index(): Response
    {
        return response()->view('docs.index');
    }

    public function navigation(): JsonResponse
    {
        $tree = $this->buildNavigationTree($this->docsPath);

        return response()->json([
            'navigation' => $tree,
        ]);
    }

    public function page(Request $request): JsonResponse
    {
        $slug = $request->query('path', 'index');

        $slug     = $this->sanitizePath($slug);
        $filePath = $this->resolveFilePath($slug);

        if (!$filePath || !file_exists($filePath)) {
            return response()->json(['error' => 'Page not found.'], 404);
        }

        $rawMarkdown = file_get_contents($filePath);
        $html        = $this->markdown->convert($rawMarkdown)->getContent();
        $headings    = $this->extractHeadings($rawMarkdown);
        $frontmatter = $this->extractFrontmatter($rawMarkdown);

        return response()->json([
            'slug'          => $slug,
            'title'         => $frontmatter['title'] ?? $this->titleFromFilename(basename($filePath)),
            'html'          => $html,
            'headings'      => $headings,
            'frontmatter'   => $frontmatter,
            'last_modified' => date('Y-m-d', filemtime($filePath)),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = trim($request->query('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];
        $files   = $this->getAllMarkdownFiles($this->docsPath);

        foreach ($files as $filePath) {
            $content = file_get_contents($filePath);
            $slug    = $this->filePathToSlug($filePath);

            if (stripos($content, $query) === false) {
                continue;
            }

            $frontmatter = $this->extractFrontmatter($content);
            $title       = $frontmatter['title'] ?? $this->titleFromFilename(basename($filePath));

            $snippet = $this->buildSnippet($content, $query, 160);

            $results[] = [
                'slug'    => $slug,
                'title'   => $title,
                'snippet' => $snippet,
                'score'   => $this->calculateRelevanceScore($content, $title, $query),
            ];
        }

        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return response()->json([
            'query'   => $query,
            'results' => array_slice($results, 0, 20),
        ]);
    }

    private function buildNavigationTree(string $dirPath, string $prefix = ''): array
    {
        $items = [];

        $meta = [];
        $metaFile = $dirPath . '/_meta.json';
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true) ?? [];
        }

        $entries = scandir($dirPath);

        $dirs  = [];
        $files = [];

        foreach ($entries as $entry) {
            if (str_starts_with($entry, '.') || $entry === '_meta.json') {
                continue;
            }

            $fullPath = $dirPath . '/' . $entry;

            if (is_dir($fullPath)) {
                $dirs[] = $entry;
            } elseif (str_ends_with($entry, '.md') && $entry !== 'index.md') {
                $files[] = $entry;
            }
        }

        $order = $meta['order'] ?? [];
        if ($order) {
            usort($dirs, fn($a, $b) => (array_search($a, $order) ?: 99) <=> (array_search($b, $order) ?: 99));
            usort($files, fn($a, $b) => (array_search($a, $order) ?: 99) <=> (array_search($b, $order) ?: 99));
        }

        $indexPath = $dirPath . '/index.md';
        if ($prefix && file_exists($indexPath)) {
            $slug    = $prefix . 'index';
            $content = file_get_contents($indexPath);
            $fm      = $this->extractFrontmatter($content);
            $items[] = [
                'type'  => 'file',
                'slug'  => $slug,
                'title' => $fm['title'] ?? $meta['title'] ?? $this->titleFromFilename($prefix ?: 'Overview'),
                'path'  => $indexPath,
            ];
        }

        foreach ($dirs as $dir) {
            $fullPath = $dirPath . '/' . $dir;
            $slugBase = $prefix . $dir . '/';
            $children = $this->buildNavigationTree($fullPath, $slugBase);

            if (empty($children)) {
                continue;
            }

            $folderMeta = [];
            $folderMetaFile = $fullPath . '/_meta.json';
            if (file_exists($folderMetaFile)) {
                $folderMeta = json_decode(file_get_contents($folderMetaFile), true) ?? [];
            }

            $items[] = [
                'type'     => 'folder',
                'slug'     => $slugBase,
                'title'    => $folderMeta['title'] ?? $meta[$dir]['title'] ?? $this->titleFromFilename($dir),
                'children' => $children,
            ];
        }

        foreach ($files as $file) {
            $fullPath = $dirPath . '/' . $file;
            $slug     = $prefix . str_replace('.md', '', $file);
            $content  = file_get_contents($fullPath);
            $fm       = $this->extractFrontmatter($content);

            $items[] = [
                'type'  => 'file',
                'slug'  => $slug,
                'title' => $fm['title'] ?? $meta[$file]['title'] ?? $this->titleFromFilename($file),
                'path'  => $fullPath,
            ];
        }

        return $items;
    }

    private function getAllMarkdownFiles(string $dirPath): array
    {
        $files   = [];
        $entries = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($entries as $entry) {
            if ($entry->getExtension() === 'md') {
                $files[] = $entry->getPathname();
            }
        }

        return $files;
    }

    private function resolveFilePath(string $slug): ?string
    {
        if ($slug === '' || $slug === 'index') {
            $path = $this->docsPath . '/index.md';
            return file_exists($path) ? $path : null;
        }

        $path = $this->docsPath . '/' . $slug . '.md';
        if (file_exists($path)) {
            return $path;
        }

        $path = $this->docsPath . '/' . $slug . '/index.md';
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    private function filePathToSlug(string $filePath): string
    {
        $relative = str_replace($this->docsPath . '/', '', $filePath);
        $slug     = str_replace('.md', '', $relative);

        return str_replace('\\', '/', $slug);
    }

    private function sanitizePath(string $path): string
    {
        $path = preg_replace('/\.\.+/', '', $path);
        $path = ltrim($path, '/\\');
        $path = preg_replace('/[^a-zA-Z0-9\-_\/]/', '', $path);

        return $path;
    }

    private function extractFrontmatter(string $content): array
    {
        if (!str_starts_with($content, '---')) {
            return [];
        }

        $end = strpos($content, '---', 3);
        if ($end === false) {
            return [];
        }

        $yaml = substr($content, 3, $end - 3);
        $data = [];

        foreach (explode("\n", trim($yaml)) as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $data[trim($key)] = trim($value);
            }
        }

        return $data;
    }

    private function extractHeadings(string $content): array
    {
        $headings = [];
        $lines    = explode("\n", $content);

        foreach ($lines as $line) {
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $matches)) {
                $level  = strlen($matches[1]);
                $text   = trim($matches[2]);
                $anchor = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text));
                $anchor = preg_replace('/\s+/', '-', trim($anchor));

                $headings[] = [
                    'level'  => $level,
                    'text'   => $text,
                    'anchor' => $anchor,
                ];
            }
        }

        return $headings;
    }

    private function buildSnippet(string $content, string $query, int $length): string
    {
        $text = preg_replace('/^---[\s\S]+?---\n/', '', $content);
        $text = preg_replace('/[#*`\[\]_>]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        $pos   = stripos($text, $query);
        $start = max(0, $pos - 60);

        $snippet = substr($text, $start, $length);

        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        if (($start + $length) < strlen($text)) {
            $snippet .= '...';
        }

        return htmlspecialchars($snippet);
    }

    private function calculateRelevanceScore(string $content, string $title, string $query): int
    {
        $score = 0;

        if (stripos($title, $query) !== false) {
            $score += 100;
        }

        $score += substr_count(strtolower($content), strtolower($query)) * 5;

        if (preg_match('/^#{2,3}\s+.*' . preg_quote($query, '/') . '/mi', $content)) {
            $score += 50;
        }

        return $score;
    }

    private function titleFromFilename(string $name): string
    {
        $name = str_replace(['.md', '-', '_'], [' ', ' ', ' '], $name);

        return ucwords(trim($name));
    }
}

