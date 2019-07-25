/* global window */
import React from 'react';
import ReactDOM from 'react-dom';
import { loadComponent } from 'lib/Injector';

window.jQuery.entwine('ss', ($) => {
  $('.js-injector-boot .ss-tag-field.entwine').entwine({
    onmatch() {
      const cmsContent = this.closest('.cms-content').attr('id');
      const context = (cmsContent)
        ? { context: cmsContent }
        : {};
      const TagField = loadComponent('TagField', context);
      const dataSchema = {
        ...this.data('schema'),
        onBlur: () => {
          this.parents('.cms-edit-form:first').trigger('change');
        }
      };

      ReactDOM.render(
        <TagField
          noHolder
          {...dataSchema}
        />,
        this[0]
      );
    },

    onunmatch() {
      ReactDOM.unmountComponentAtNode(this[0]);
    }
  });
});
