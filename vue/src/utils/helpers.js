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

/**
 * Persistence: Open/Init IndexedDB for photo drafts
 */
const DB_NAME = 'pintventory_db';
const STORE_NAME = 'photo_drafts';

/**
 * Opens and initializes the IndexedDB database for draft persistence.
 * 
 * @private
 * @returns {Promise<IDBDatabase>}
 */
function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, 1);
    request.onupgradeneeded = (e) => {
      const db = e.target.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME);
      }
    };
    request.onsuccess = (e) => resolve(e.target.result);
    request.onerror = (e) => reject(e.target.error);
  });
}

/**
 * Save pending photos to IndexedDB
 * 
 * @param {string} key - Unique key for the draft (e.g. 'inventory_new' or item ID)
 * @param {File[]} files - Array of File objects
 * @returns {Promise<void>}
 */
export async function saveDraftPhotos(key, files) {
  try {
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, 'readwrite');
    const store = tx.objectStore(STORE_NAME);
    // We store the raw file objects. IndexedDB supports File/Blob.
    store.put(files, key);
    return new Promise((resolve, reject) => {
      tx.oncomplete = () => resolve();
      tx.onerror = (e) => reject(e.target.error);
    });
  } catch (e) {
    console.error('IndexedDB save failed', e);
  }
}

/**
 * Load pending photos from IndexedDB
 * 
 * @param {string} key - Unique key for the draft
 * @returns {Promise<File[]>}
 */
export async function loadDraftPhotos(key) {
  try {
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, 'readonly');
    const store = tx.objectStore(STORE_NAME);
    const request = store.get(key);
    return new Promise((resolve, reject) => {
      request.onsuccess = () => resolve(request.result || []);
      request.onerror = (e) => reject(e.target.error);
    });
  } catch (e) {
    console.error('IndexedDB load failed', e);
    return [];
  }
}

/**
 * Clear photo drafts from IndexedDB
 * 
 * @param {string} key - Unique key for the draft to clear
 * @returns {Promise<void>}
 */
export async function clearDraftPhotos(key) {
  try {
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, 'readwrite');
    const store = tx.objectStore(STORE_NAME);
    store.delete(key);
  } catch (e) {
    console.error('IndexedDB clear failed', e);
  }
}
