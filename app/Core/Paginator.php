<?php
/**
 * Pagination Helper
 * Provides pagination functionality for database queries
 */

class Paginator {
    private int $totalRecords;
    private int $perPage;
    private int $currentPage;
    private int $totalPages;
    private string $baseUrl;
    
    public function __construct(int $totalRecords, int $perPage = 20, int $currentPage = 1, string $baseUrl = '') {
        $this->totalRecords = max(0, $totalRecords);
        $this->perPage = max(1, $perPage);
        $this->currentPage = max(1, $currentPage);
        $this->totalPages = $this->totalRecords > 0 ? (int)ceil($this->totalRecords / $this->perPage) : 1;
        $this->currentPage = min($this->currentPage, $this->totalPages);
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Get SQL LIMIT clause
     */
    public function getLimitSql(): string {
        $offset = ($this->currentPage - 1) * $this->perPage;
        return "LIMIT {$this->perPage} OFFSET {$offset}";
    }
    
    /**
     * Get offset value
     */
    public function getOffset(): int {
        return ($this->currentPage - 1) * $this->perPage;
    }
    
    /**
     * Get limit value
     */
    public function getLimit(): int {
        return $this->perPage;
    }
    
    /**
     * Get current page
     */
    public function getCurrentPage(): int {
        return $this->currentPage;
    }
    
    /**
     * Get total pages
     */
    public function getTotalPages(): int {
        return $this->totalPages;
    }
    
    /**
     * Get total records
     */
    public function getTotalRecords(): int {
        return $this->totalRecords;
    }
    
    /**
     * Get per page
     */
    public function getPerPage(): int {
        return $this->perPage;
    }
    
    /**
     * Check if there are previous pages
     */
    public function hasPrevious(): bool {
        return $this->currentPage > 1;
    }
    
    /**
     * Check if there are next pages
     */
    public function hasNext(): bool {
        return $this->currentPage < $this->totalPages;
    }
    
    /**
     * Get previous page number
     */
    public function getPreviousPage(): int {
        return max(1, $this->currentPage - 1);
    }
    
    /**
     * Get next page number
     */
    public function getNextPage(): int {
        return min($this->totalPages, $this->currentPage + 1);
    }
    
    /**
     * Get range of records being displayed
     */
    public function getRange(): array {
        if ($this->totalRecords === 0) {
            return ['from' => 0, 'to' => 0];
        }
        
        $from = ($this->currentPage - 1) * $this->perPage + 1;
        $to = min($this->currentPage * $this->perPage, $this->totalRecords);
        
        return ['from' => $from, 'to' => $to];
    }
    
    /**
     * Get page numbers to display
     */
    public function getPageNumbers(int $maxLinks = 7): array {
        if ($this->totalPages <= $maxLinks) {
            return range(1, $this->totalPages);
        }
        
        $half = floor($maxLinks / 2);
        $start = max(1, $this->currentPage - $half);
        $end = min($this->totalPages, $start + $maxLinks - 1);
        
        if ($end - $start < $maxLinks - 1) {
            $start = max(1, $end - $maxLinks + 1);
        }
        
        return range($start, $end);
    }
    
    /**
     * Build URL with page parameter
     */
    public function buildUrl(int $page, array $params = []): string {
        $params['page'] = $page;
        
        // Preserve existing query parameters
        if (empty($this->baseUrl)) {
            $this->baseUrl = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        }
        
        // Merge with existing GET parameters
        $existingParams = $_GET;
        unset($existingParams['page']);
        $params = array_merge($existingParams, $params);
        
        $query = http_build_query($params);
        return $this->baseUrl . ($query ? '?' . $query : '');
    }
    
    /**
     * Render pagination HTML
     */
    public function render(): string {
        if ($this->totalPages <= 1) {
            return '';
        }
        
        $range = $this->getRange();
        $pages = $this->getPageNumbers();
        
        $html = '<div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white border-t border-slate-200">';
        
        // Info text
        $html .= '<div class="text-sm text-slate-600">';
        $html .= "Showing <span class=\"font-semibold\">{$range['from']}</span> to <span class=\"font-semibold\">{$range['to']}</span> of <span class=\"font-semibold\">{$this->totalRecords}</span> results";
        $html .= '</div>';
        
        // Pagination buttons
        $html .= '<div class="flex items-center gap-1">';
        
        // Previous button
        if ($this->hasPrevious()) {
            $url = h($this->buildUrl($this->getPreviousPage()));
            $html .= "<a href=\"{$url}\" class=\"px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50\">Previous</a>";
        } else {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">Previous</span>';
        }
        
        // Page numbers
        $pageNumbers = $this->getPageNumbers();
        
        // First page + ellipsis
        if ($pageNumbers[0] > 1) {
            $url = h($this->buildUrl(1));
            $html .= "<a href=\"{$url}\" class=\"hidden sm:inline-flex px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50\">1</a>";
            if ($pageNumbers[0] > 2) {
                $html .= '<span class="hidden sm:inline-flex px-3 py-2 text-sm font-medium text-slate-400">...</span>';
            }
        }
        
        // Page numbers
        foreach ($pageNumbers as $page) {
            $url = h($this->buildUrl($page));
            if ($page === $this->currentPage) {
                $html .= "<span class=\"hidden sm:inline-flex px-3 py-2 text-sm font-medium text-white bg-slate-900 border border-slate-900 rounded-lg\">{$page}</span>";
            } else {
                $html .= "<a href=\"{$url}\" class=\"hidden sm:inline-flex px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50\">{$page}</a>";
            }
        }
        
        // Last page + ellipsis
        if ($pageNumbers[count($pageNumbers) - 1] < $this->totalPages) {
            if ($pageNumbers[count($pageNumbers) - 1] < $this->totalPages - 1) {
                $html .= '<span class="hidden sm:inline-flex px-3 py-2 text-sm font-medium text-slate-400">...</span>';
            }
            $url = h($this->buildUrl($this->totalPages));
            $html .= "<a href=\"{$url}\" class=\"hidden sm:inline-flex px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50\">{$this->totalPages}</a>";
        }
        
        // Next button
        if ($this->hasNext()) {
            $url = h($this->buildUrl($this->getNextPage()));
            $html .= "<a href=\"{$url}\" class=\"px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50\">Next</a>";
        } else {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">Next</span>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

/**
 * Helper function to create paginator
 */
function paginate(int $totalRecords, int $perPage = 20, ?int $currentPage = null): Paginator {
    if ($currentPage === null) {
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    }
    return new Paginator($totalRecords, $perPage, $currentPage);
}
