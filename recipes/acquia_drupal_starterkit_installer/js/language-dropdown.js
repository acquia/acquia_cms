/**
 * @file
 * Creation and interactions for the language dropdown.
 */

((Drupal, once, drupalSettings) => {

  /**
   * Gets the current URL and queryStrings, sets the language, and returns the
   * full URL.
   *
   * @param {string} langcode - The language code (e.g. 'es').
   * @returns {string}
   */
  function getLanguageURL(langcode) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('langcode', langcode);


    return `${window.location.pathname}?${urlParams}`;
  }

  /**
   * Creates the element.
   */
  function createElement() {
    const dropdownWrapper = document.querySelector('.cms-installer__language');
    const dropdown = document.createElement('div');

    dropdown.setAttribute('id', 'cms-installer-language-dropdown');
    dropdown.classList.add('cms-installer__language-dropdown');
    dropdown.innerHTML = `
      <ul class="cms-installer__language-list">
      </ul>
    `;

    const languageList = dropdown.querySelector('.cms-installer__language-list');

    for (const langcode in drupalSettings.languages) {
      const languageElement = document.createElement('li');
      languageElement.classList.add('cms-installer__list-item');
      languageElement.innerHTML = `
        <a class="cms-installer__list-link" lang="${langcode}" href="${getLanguageURL(langcode)}">${drupalSettings.languages[langcode]}</a>
      `;
      languageList.append(languageElement);
    }

    dropdownWrapper.append(dropdown);
  }

  /**
   * Toggles the visibility of the dropdown.
   * @param {Evebt} e - The click event.
   */
  function toggleDropdownVisibility(e) {
    const originalVisibility = e.currentTarget.getAttribute('aria-expanded') === 'true';
    e.currentTarget.setAttribute('aria-expanded', !originalVisibility);
  }

  /**
   * Sets up interactions (toggling visibility, etc).
   */
  function setupInteractions() {
    const dropdownButton = document.querySelector('.cms-installer__language-button');
    dropdownButton.setAttribute('aria-expanded', 'false');
    dropdownButton.setAttribute('aria-controls', 'cms-installer-language-dropdown');
    dropdownButton.addEventListener('click', toggleDropdownVisibility);

    // Close on ESC.
    document.addEventListener('keyup', (e) => {
      if (e.key === 'Escape') {
        dropdownButton.setAttribute('aria-expanded', 'false');
      }
    });

    // Close when clicking outside.
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.cms-installer__language')) {
        dropdownButton.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /**
   * Let's do this!
   */
  function init() {
    createElement();
    setupInteractions();
  }

  /**
   * Attaches the behavior to the language wrapper.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   */
  Drupal.behaviors.installerLanguageDropdown = {
    attach(context) {
      once('installer-language-dropdown', '[data-drupal-selector="cms-language-dropdown"]', context).forEach(
        init,
      );
    },
  };
})(Drupal, once, drupalSettings);
