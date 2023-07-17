/* global window */
import React from 'react';
import { createRoot } from 'react-dom/client';
import { loadComponent } from 'lib/Injector';

window.jQuery.entwine('ss', ($) => {
  $('.js-injector-boot .ss-tag-field.entwine').entwine({
    ReactRoot: null,

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

      let root = this.getReactRoot();
      if (!root) {
        root = createRoot(this[0]);
        this.setReactRoot(root);
      }
      root.render(
        <TagField
          noHolder
          {...dataSchema}
        />
      );
    },

    onunmatch() {
      const root = this.getReactRoot();
      if (root) {
        root.unmount();
        this.setReactRoot(null);
      }
    }
  });

  $('.cms-edit-form').entwine({
    getChangeTrackerOptions() {
      // Figure out if we're still returning the default value
      const isDefault = (this.entwineData('ChangeTrackerOptions') === undefined);
      // Get the current options
      let opts = this._super();

      if (isDefault) {
        // If it is the default then...
        // clone the object (so we don't modify the original),
        opts = $.extend({}, opts);
        // modify it,
        opts.ignoreFieldSelector += ', .ss-tag-field .no-change-track :input';
        // then set the clone as the value on this element
        // (so next call to this method gets this same clone)
        this.setChangeTrackerOptions(opts);
      }

      return opts;
    }
  });
});
