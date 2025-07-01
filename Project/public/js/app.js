console.log("App JS loaded!");

// Detect base path from script tag
(function() {
  // Try to detect base path from script URL
  const scripts = document.getElementsByTagName('script');
  const currentScript = scripts[scripts.length - 1];
  let scriptSrc = currentScript.src || '';
  
  // Extract base path from the script source
  const pathParts = scriptSrc.split('/');
  pathParts.pop(); // Remove script name
  pathParts.pop(); // Remove 'js' directory
  
  // Set global base path
  window.basePath = pathParts.join('/');
  
  console.log("Base path detected:", window.basePath);
})();

// Global error handler to prevent silent failures
window.addEventListener('error', function(event) {
  console.error('Global error:', event.error);
});

// Promise rejection handler
window.addEventListener('unhandledrejection', function(event) {
  console.error('Unhandled promise rejection:', event.reason);
});

// Utility functions
window.utils = {
  // Check if an element exists
  elementExists: function(selector) {
    return document.querySelector(selector) !== null;
  },
  
  // Safely get element by ID with optional fallback
  getElementById: function(id, fallback = null) {
    const element = document.getElementById(id);
    return element || fallback;
  },
  
  // Safe DOM manipulation - only update if element exists
  updateElement: function(id, content, asHTML = false) {
    const element = document.getElementById(id);
    if (element) {
      if (asHTML) {
        element.innerHTML = content;
      } else {
        element.textContent = content;
      }
      return true;
    }
    return false;
  },
  
  // Safely add event listener
  addEventListenerSafe: function(selector, event, handler) {
    const element = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (element) {
      element.addEventListener(event, handler);
      return true;
    }
    return false;
  },
  
  // Safely handle API calls with automatic error handling
  fetchAPI: function(url, options = {}) {
    // Use base path for relative URLs
    if (url.startsWith('/') && !url.startsWith('//')) {
      url = window.basePath + url;
    }
    
    return fetch(url, options)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error ${response.status}`);
        }
        return response.json();
      })
      .catch(error => {
        console.error(`API error (${url}):`, error);
        throw error;
      });
  }
};
