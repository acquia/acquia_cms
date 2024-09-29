(function (Drupal) {

  /**
   * Renders a client-side progress bar.
   *
   * This is for the Acquia Acquia Drupal Starterkit installer and is not meant to be reused.
   */
  Drupal.theme.progressBar = function (id) {
    const escapedId = Drupal.checkPlain(id);
    return (
      `<div id="${escapedId}" class="progress" aria-live="polite">` +
      // '<div class="progress__label">&nbsp;</div>' +
      '<div class="progress__track"><div class="progress__bar"></div></div>' +
      // '<div class="progress__percentage"></div>' +
      // '<div class="progress__description">&nbsp;</div>' +
      '</div>'
    );
  };

})(Drupal);
