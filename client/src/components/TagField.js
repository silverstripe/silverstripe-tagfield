import React, { Component } from 'react';
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

class TagField extends Component {
  constructor(props) {
    super(props);

    this.selectComponentRef = React.createRef();

    this.state = {
      initalState: props.value ? props.value : [],
      hasChanges: false,
    };

    if (!this.isControlled()) {
      this.state = {
        ...this.state,
        value: props.value,
      };
    }

    this.handleChange = this.handleChange.bind(this);
    this.handleOnBlur = this.handleOnBlur.bind(this);
    this.isValidNewOption = this.isValidNewOption.bind(this);
    this.getOptions = this.getOptions.bind(this);
    this.fetchOptions = debounce(this.fetchOptions, 500);
  }

  componentDidUpdate(previousProps, previousState) {
    if (previousState.hasChanges !== this.state.hasChanges) {
      const element = this.selectComponentRef.current.inputRef;
      const event = new Event('change', { bubbles: true });
      element.dispatchEvent(event);
    }
  }

  /**
   * Get the options that should be shown to the user for this tagfield, optionally filtering by the
   * given string input
   *
   * @param {string} input
   * @return {Promise<Array<Object>>|Promise<{options: Array<Object>}>}
   */
  getOptions(input) {
    const { lazyLoad, options } = this.props;

    if (!lazyLoad) {
      return Promise.resolve(options);
    }

    if (!input) {
      return Promise.resolve([]);
    }
    return this.fetchOptions(input);
  }

  /**
   * Handle a change, either calling the change handler provided (if controlled) or updating
   * internal state of this component
   *
   * @param {string} value
   */
  handleChange(value) {
    this.setState({
      hasChanges: false
    });

    if (JSON.stringify(this.state.initalState) !== JSON.stringify(value)) {
      this.setState({
        hasChanges: true
      });
    }

    if (this.isControlled()) {
      this.props.onChange(value);
      return;
    }

    this.setState({
      value,
    });
  }

  /**
   * Determine if this input should be "controlled" or not. Controlled inputs should rely on their
   * value coming from props and a change handler provided to update the state stored elsewhere.
   * This is specifically the case for use with `redux-form`.
   *
   * @return {boolean}
   */
  isControlled() {
    return typeof this.props.onChange === 'function';
  }

  /**
   * Required to prevent TagField being cleared on blur
   *
   * @link https://github.com/JedWatson/react-select/issues/805
   */
  handleOnBlur() {}

  /**
   * Initiate a request to fetch options, optionally using the given string as a filter.
   *
   * @param {string} input
   * @return {Promise<{options: Array<Object>}>}
   */
  fetchOptions(input) {
    const { optionUrl, labelKey, valueKey } = this.props;
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
  }

  /**
   * Check if a new option can be created based on a given input
   * @param {string} inputValue
   * @param {array|object} value
   * @param {array} currentOptions
   * @returns {boolean}
   */
  isValidNewOption(inputValue, value, currentOptions) {
    const { valueKey } = this.props;

    // Don't allow empty options
    if (!inputValue) {
      return false;
    }

    // Don't repeat the currently selected option
    if (Array.isArray(value)) {
      if (this.valueInOptions(inputValue, value, valueKey)) {
        return false;
      }
    } else if (inputValue === value[valueKey]) {
      return false;
    }

    // Don't repeat any existing option
    return !this.valueInOptions(inputValue, currentOptions, valueKey);
  }

  /**
   * Check if a value is in an array of options already
   * @param {string} value
   * @param {array} options
   * @param {string} valueKey
   * @returns {boolean}
   */
  valueInOptions(value, options, valueKey) {
    // eslint-disable-next-line no-restricted-syntax
    for (const item of options) {
      if (value === item[valueKey]) {
        return true;
      }
    }
    return false;
  }

  render() {
    const {
      lazyLoad,
      options,
      creatable,
      multi,
      disabled,
      labelKey,
      valueKey,
      SelectComponent,
      AsyncCreatableSelectComponent,
      AsyncSelectComponent,
      CreatableSelectComponent,
      ...passThroughAttributes
    } = this.props;

    const optionAttributes = lazyLoad
      ? { loadOptions: this.getOptions }
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
    if (!this.isControlled()) {
      passThroughAttributes.value = this.state.value;
    }

    // if this is a single select then we just need the first value
    if (!multi && passThroughAttributes.value) {
      if (Object.keys(passThroughAttributes.value).length > 0) {
        const value =
          passThroughAttributes.value[
            Object.keys(passThroughAttributes.value)[0]
          ];

        if (typeof value === 'object') {
          passThroughAttributes.value = value;
        }
      }
    }

    const changedClassName = this.state.hasChanges ? '' : 'no-change-track';

    return (
      <EmotionCssCacheProvider>
        <DynamicSelect
          {...passThroughAttributes}
          isMulti={multi}
          isDisabled={disabled}
          cacheOptions
          onChange={this.handleChange}
          onBlur={this.handleOnBlur}
          {...optionAttributes}
          getOptionLabel={(option) => option[labelKey]}
          getOptionValue={(option) => option[valueKey]}
          noOptionsMessage={({ inputValue }) => (inputValue ? i18n._t('TagField.NO_OPTIONS', 'No options') : i18n._t('TagField.TYPE_TO_SEARCH', 'Type to search'))}
          isValidNewOption={this.isValidNewOption}
          getNewOptionData={(inputValue, label) => ({ [labelKey]: label, [valueKey]: inputValue })}
          classNamePrefix="ss-tag-field"
          className={changedClassName}
          ref={this.selectComponentRef}
        />
      </EmotionCssCacheProvider>
    );
  }
}

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

TagField.defaultProps = {
  labelKey: 'Title',
  valueKey: 'Value',
  disabled: false,
  lazyLoad: false,
  creatable: false,
  multi: false,
  SelectComponent: Select,
  AsyncCreatableSelectComponent: AsyncCreatableSelect,
  AsyncSelectComponent: AsyncSelect,
  CreatableSelectComponent: CreatableSelect,
};

export { TagField as Component };

export default fieldHolder(TagField);
