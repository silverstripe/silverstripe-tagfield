import React, { Component, PropTypes } from 'react';
import Select from 'react-select';
import fetch from 'isomorphic-fetch';
import url from 'url';

class TagField extends Component {
  constructor(props) {
    super(props);

    this.state = {
      value: props.value,
    };

    this.onChange = this.onChange.bind(this);
    this.getOptions = this.getOptions.bind(this);
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
    if (!this.props.lazyLoad) {
      return Promise.resolve({ options: this.props.options });
    }

    if (!input) {
      return Promise.resolve({ options: [] });
    }

    const fetchURL = url.parse(this.props.optionUrl, true);
    fetchURL.query.term = input;

    return fetch(url.format(fetchURL), { credentials: 'same-origin' })
      .then((response) => response.json())
      .then((json) => ({ options: json.items }));
  }

  render() {
    const optionAttributes = this.props.lazyLoad
      ? { loadOptions: this.getOptions }
      : { options: this.props.options };

    let SelectComponent = Select;
    if (this.props.lazyLoad && this.props.creatable) {
      SelectComponent = Select.AsyncCreatable;
    } else if (this.props.lazyLoad) {
      SelectComponent = Select.Async;
    } else if (this.props.creatable) {
      SelectComponent = Select.Creatable;
    }

    return (
      <SelectComponent
        name={this.props.name}
        multi={this.props.multiple}
        value={this.state.value}
        onChange={this.onChange}
        onBlur={this.props.onBlur}
        valueKey={this.props.valueKey}
        labelKey={this.props.labelKey}
        inputProps={{ className: 'no-change-track' }}
        {...optionAttributes}
      />
    );
  }
}

TagField.propTypes = {
  name: PropTypes.string.required,
  labelKey: PropTypes.string.required,
  valueKey: PropTypes.string.required,
  lazyLoad: PropTypes.bool.required,
  creatable: PropTypes.bool.required,
  multiple: PropTypes.bool.required,
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
  disabled: false
};

export default TagField;
