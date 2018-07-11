import React, {Component, PropTypes} from 'react';
import Select from 'react-select';
import fetch from 'isomorphic-fetch';
import url from 'url';

class TagField extends Component {
  constructor(props) {
    super(props);

    this.state = {
      value: props.value,
    }

    this.onChange = this.onChange.bind(this);
  }

  getOptions(input) {
    if (!this.props.lazyLoad) {
      return Promise.resolve({options: this.props.options});
    }

    if (!input) {
      return Promise.resolve({options: []});
    }

    const fetchURL = url.parse(this.props.optionUrl, true);
    fetchURL.query.term = input;

    return fetch(url.format(fetchURL))
      .then((response) => response.json())
      .then((json) => {
        return {options: json.items}
      })
  }

  onChange(value) {
    this.setState({
      value: value
    })
  }

  render() {
    const optionAttributes = this.props.lazyLoad ? { loadOptions: this.getOptions } : { options: this.props.options };

    let SelectComponent = Select;
    if (this.props.lazyLoad && this.props.creatable) {
      SelectComponent = Select.AsyncCreatable;
    } else if (this.props.lazyLoad) {
      SelectComponent = Select.Async;
    } else if (this.props.creatable) {
      SelectComponent = Select.Creatable;
    }

    return (
      <div>
        <input type="hidden" name={this.props.name} value={this.state.value} defaultValue={this.props.value} />
        <SelectComponent
          multi={this.props.multiple}
          value={this.state.value}
          onChange={this.onChange}
          valueKey={this.props.valueKey}
          labelKey={this.props.labelKey}
          {...optionAttributes}
        />
      </div>
    )
  }
}

TagField.propTypes = {
  name: PropTypes.string.required,
  labelKey: PropTypes.string.required,
  valueKey: PropTypes.string.required,
  lazyLoad: PropTypes.bool.required,
  creatable: PropTypes.bool.required,
  multiple: PropTypes.bool.required,
  options: PropTypes.arrayOf(PropTypes.object),
  optionUrl: PropTypes.string,
  value: PropTypes.any,
};

TagField.defaultProps = {
  labelKey: 'Title',
  valueKey: 'Value',
}

export default TagField;
