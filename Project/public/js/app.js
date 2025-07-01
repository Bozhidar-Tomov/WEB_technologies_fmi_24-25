console.log("App JS loaded!");

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
