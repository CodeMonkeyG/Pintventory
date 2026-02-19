/**
 * Debounce a function call to prevent excessive executions
 * 
 * @param {Function} func - The function to debounce
 * @param {number} [wait=300] - Milliseconds to wait before executing
 * @returns {Function} The debounced function
 * 
 * @example
 * const debouncedSearch = debounce((query) => fetchResults(query), 300);
 * input.addEventListener('input', (e) => debouncedSearch(e.target.value));
 */
export function debounce(func, wait = 300) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Revoke blob URLs to prevent memory leaks
 * 
 * @param {string[]} urls - Array of blob URLs to revoke
 * 
 * @example
 * revokeBlobUrls(['blob:http://example.com/123', 'blob:http://example.com/456']);
 */
export function revokeBlobUrls(urls) {
  urls.forEach(url => {
    if (url && url.startsWith('blob:')) {
      URL.revokeObjectURL(url);
    }
  });
}
