import React, { useState, useRef, useEffect } from 'react';
import Select from 'react-select';
import AsyncSelect from 'react-select/async';
import AsyncCreatableSelect from 'react-select/async-creatable';
import CreatableSelect from 'react-select/creatable';
import EmotionCssCacheProvider from 'containers/EmotionCssCacheProvider/EmotionCssCacheProvider';
import i18n from 'i18n';
import fetch from 'isomorphic-fetch';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import url from 'url';
import debounce from 'debounce-promise';
import PropTypes from 'prop-types';

const TagField = ({
  lazyLoad = false,
  options,
  creatable = false,
  multi = false,
  disabled = false,
  labelKey = 'Title',
  valueKey = 'Value',
  SelectComponent = Select,
  AsyncCreatableSelectComponent = AsyncCreatableSelect,
  AsyncSelectComponent = AsyncSelect,
  CreatableSelectComponent = CreatableSelect,
  value: propValue,
  onChange,
  optionUrl,
  ...passThroughAttributesFromProps
}) => {
  const selectComponentRef = useRef(null);

  const [hasChanges, setHasChanges] = useState(false);
  const [stateValue, setStateValue] = useState(propValue);

  useEffect(() => {
    const element = selectComponentRef.current.inputRef;
    const event = new Event('change', { bubbles: true });
    element.dispatchEvent(event);
  }, [hasChanges]);

  /**
   * Initiate a request to fetch options, optionally using the given string as a filter.
   * The actual fetch is delayed by 500ms to avoid excessive requests while the user is typing.
   *
   * @param {string} input
   * @return {Promise<{options: Array<Object>}>}
   */
  const fetchOptions = debounce((input) => {
    const fetchURL = url.parse(optionUrl, true);
    fetchURL.query.term = input;
    return fetch(url.format(fetchURL), { credentials: 'same-origin' })
      .then((response) => response.json())
      .then((json) => json.items.map(
        (item) => ({
          [labelKey]: item.Title,
          [valueKey]: item.Value,
          Selected: item.Selected,
        })
      ));
  }, 500);

  /**
   * Get the options that should be shown to the user for this tagfield, optionally filtering by the
   * given string input
   *
   * @param {string} input
   * @return {Promise<Array<Object>>|Promise<{options: Array<Object>}>}
   */
  const getOptions = (input) => {
    if (!lazyLoad) {
      return Promise.resolve(options);
    }

    if (!input) {
      return Promise.resolve([]);
    }
    return fetchOptions(input);
  };

  /**
   * Determine if this input should be "controlled" or not. Controlled inputs should rely on their
   * value coming from props and a change handler provided to update the state stored elsewhere.
   * This is specifically the case for use with `redux-form`.
   *
   * @return {boolean}
   */
  const isControlled = () => typeof onChange === 'function';

  /**
   * Handle a change, either calling the change handler provided (if controlled) or updating
   * internal state of this component
   *
   * @param {string} newValue
   */
  const handleChange = (newValue) => {
    setHasChanges(false);

    if (JSON.stringify(propValue || []) !== JSON.stringify(newValue)) {
      setHasChanges(true);
    }

    if (isControlled()) {
      onChange(newValue);
      return;
    }

    setStateValue(newValue);
  };

  /**
   * Required to prevent TagField being cleared on blur
   *
   * @link https://github.com/JedWatson/react-select/issues/805
   */
  const handleOnBlur = () => {};

  /**
   * Check if a value is in an array of options already
   * @param {string} checkValue
   * @param {array} checkOptions
   * @param {string} key
   * @returns {boolean}
   */
  const valueInOptions = (checkValue, checkOptions, key) => {
    // eslint-disable-next-line no-restricted-syntax
    for (const item of checkOptions) {
      if (checkValue === item[key]) {
        return true;
      }
    }
    return false;
  };

  /**
   * Check if a new option can be created based on a given input
   * @param {string} inputValue
   * @param {array|object} checkValue
   * @param {array} currentOptions
   * @returns {boolean}
   */
  const isValidNewOption = (inputValue, checkValue, currentOptions) => {
    // Don't allow empty options
    if (!inputValue) {
      return false;
    }

    // Don't repeat the currently selected option
    if (Array.isArray(checkValue)) {
      if (valueInOptions(inputValue, checkValue, valueKey)) {
        return false;
      }
    } else if (inputValue === checkValue[valueKey]) {
      return false;
    }

    // Don't repeat any existing option
    return !valueInOptions(inputValue, currentOptions, valueKey);
  };

  const optionAttributes = lazyLoad
    ? { loadOptions: getOptions }
    : { options };

  let DynamicSelect = SelectComponent;
  if (lazyLoad && creatable) {
    DynamicSelect = AsyncCreatableSelectComponent;
  } else if (lazyLoad) {
    DynamicSelect = AsyncSelectComponent;
  } else if (creatable) {
    DynamicSelect = CreatableSelectComponent;
  }

  // Update the value to passthrough with the kept state provided this component is not
  // "controlled"
  const passThroughAttributes = {
    ...passThroughAttributesFromProps,
    optionUrl,
  };
  if (!isControlled()) {
    passThroughAttributes.value = stateValue;
  }

  // if this is a single select then we just need the first value
  if (!multi && passThroughAttributes.value) {
    if (Object.keys(passThroughAttributes.value).length > 0) {
      const singleValue =
        passThroughAttributes.value[
          Object.keys(passThroughAttributes.value)[0]
        ];

      if (typeof singleValue === 'object') {
        passThroughAttributes.value = singleValue;
      }
    }
  }

  const changedClassName = hasChanges ? '' : 'no-change-track';

  return (
    <EmotionCssCacheProvider>
      <DynamicSelect
        {...passThroughAttributes}
        isMulti={multi}
        isDisabled={disabled}
        cacheOptions
        onChange={handleChange}
        onBlur={handleOnBlur}
        {...optionAttributes}
        getOptionLabel={(option) => option[labelKey]}
        getOptionValue={(option) => option[valueKey]}
        noOptionsMessage={({ inputValue }) => (inputValue ? i18n._t('TagField.NO_OPTIONS', 'No options') : i18n._t('TagField.TYPE_TO_SEARCH', 'Type to search'))}
        isValidNewOption={isValidNewOption}
        getNewOptionData={(inputValue, label) => ({ [labelKey]: label, [valueKey]: inputValue })}
        classNamePrefix="ss-tag-field"
        className={changedClassName}
        ref={selectComponentRef}
      />
    </EmotionCssCacheProvider>
  );
};

TagField.propTypes = {
  name: PropTypes.string.isRequired,
  labelKey: PropTypes.string.isRequired,
  valueKey: PropTypes.string.isRequired,
  lazyLoad: PropTypes.bool,
  creatable: PropTypes.bool,
  multi: PropTypes.bool,
  disabled: PropTypes.bool,
  options: PropTypes.arrayOf(PropTypes.object),
  optionUrl: PropTypes.string,
  value: PropTypes.any,
  onChange: PropTypes.func,
  onBlur: PropTypes.func,
  SelectComponent: PropTypes.oneOfType([PropTypes.object, PropTypes.func]),
  AsyncCreatableSelectComponent: PropTypes.oneOfType([PropTypes.object, PropTypes.func]),
  AsyncSelectComponent: PropTypes.oneOfType([PropTypes.object, PropTypes.func]),
  CreatableSelectComponent: PropTypes.oneOfType([PropTypes.object, PropTypes.func]),
};

export { TagField as Component };

export default fieldHolder(TagField);
