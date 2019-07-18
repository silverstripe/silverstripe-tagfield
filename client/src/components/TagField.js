import React, { Component } from 'react';
import Select from 'react-select';
import fetch from 'isomorphic-fetch';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import url from 'url';
import debounce from 'debounce-promise';
import PropTypes from 'prop-types';


class TagField extends Component {
  constructor(props) {
    super(props);

    this.state = {
      value: props.value,
    };

    this.onChange = this.onChange.bind(this);
    this.handleOnBlur = this.handleOnBlur.bind(this);
    this.getOptions = this.getOptions.bind(this);
    this.fetchOptions = debounce(this.fetchOptions, 500);
  }

  onChange(value) {
    this.setState({
      value
    });

    if (typeof this.props.onChange === 'function') {
      this.props.onChange(value);
    }
  }

  getOptions(input) {
    const { lazyLoad, options } = this.props;

    if (!lazyLoad) {
      return Promise.resolve({ options });
    }

    if (!input) {
      return Promise.resolve({ options: [] });
    }

    return this.fetchOptions(input);
  }

  /**
   * Required to prevent TagField being cleared on blur
   *
   * @link https://github.com/JedWatson/react-select/issues/805
   */
  handleOnBlur() {

  }

  fetchOptions(input) {
    const { optionUrl, labelKey, valueKey } = this.props;
    const fetchURL = url.parse(optionUrl, true);
    fetchURL.query.term = input;

    return fetch(url.format(fetchURL), { credentials: 'same-origin' })
      .then((response) => response.json())
      .then((json) => ({
        options: json.items.map(item => ({
          [labelKey]: item.Title,
          [valueKey]: item.Value,
        }))
      }));
  }

  render() {
    const {
      lazyLoad,
      options,
      creatable,
      ...passThroughAttributes
    } = this.props;

    const optionAttributes = lazyLoad
      ? { loadOptions: this.getOptions }
      : { options };

    let SelectComponent = Select;
    if (lazyLoad && creatable) {
      SelectComponent = Select.AsyncCreatable;
    } else if (lazyLoad) {
      SelectComponent = Select.Async;
    } else if (creatable) {
      SelectComponent = Select.Creatable;
    }

    passThroughAttributes.value = this.state.value;

    return (
      <SelectComponent
        {...passThroughAttributes}
        onChange={this.onChange}
        onBlur={this.handleOnBlur}
        inputProps={{ className: 'no-change-track' }}
        {...optionAttributes}
      />
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
};

TagField.defaultProps = {
  labelKey: 'Title',
  valueKey: 'Value',
  disabled: false,
  lazyLoad: false,
  creatable: false,
  multi: false,
};

export { TagField as Component };

export default fieldHolder(TagField);
