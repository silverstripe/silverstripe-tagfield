/* eslint-disable import/no-extraneous-dependencies */
/* global jest, describe, beforeEach, it, expect, setTimeout, document */

jest.mock('isomorphic-fetch');

import React from 'react';
import Enzyme, { shallow } from 'enzyme';
import Adapter from 'enzyme-adapter-react-15.4';
import { Component as TagField } from '../TagField';
import Select from 'react-select';
import fetch from 'isomorphic-fetch';

Enzyme.configure({ adapter: new Adapter() });

describe('TagField', () => {
  let props;

  beforeEach(() => {
    props = {
      name: 'Test',
      labelKey: 'label',
      valueKey: 'value',
      lazyLoad: false,
      creatable: false,
      multi: true,
    };
  });

  describe('should render a Select component with type', () => {
    it('Select', () => {
      const wrapper = shallow(
        <TagField {...props} />
      );
      expect(wrapper.find(Select).length).toBe(1);
    });
    it('Select.Creatable with creatable option', () => {
      props.creatable = true;
      const wrapper = shallow(
        <TagField {...props} />
      );
      expect(wrapper.find(Select.Creatable).length).toBe(1);
    });
    it('Select.Async with lazyLoad option', () => {
      props.lazyLoad = true;
      const wrapper = shallow(
        <TagField {...props} />
      );
      expect(wrapper.find(Select.Async).length).toBe(1);
    });
    it('Select.AsyncCreatable with both creatable and lazyLoad options', () => {
      props.creatable = true;
      props.lazyLoad = true;
      const wrapper = shallow(
        <TagField {...props} />
      );
      expect(wrapper.find(Select.AsyncCreatable).length).toBe(1);
    });
  });

  describe('with lazyLoad on and given a URL', () => {
    let wrapper;

    beforeEach(() => {
      props.lazyLoad = true;
      props.optionUrl = 'localhost/some-fetch-url';

      wrapper = shallow(
        <TagField {...props} />
      );

      fetch.mockImplementation(() => Promise.resolve({
        json: () => ({}),
      }));
    });

    it('should fetch the URL for results', done => {
      wrapper.instance().getOptions('a');

      setTimeout(() => {
        expect(fetch).toBeCalledWith('localhost/some-fetch-url?term=a', expect.anything());
        done();
      }, 500);
    });
  });
});
