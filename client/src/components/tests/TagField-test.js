/* eslint-disable import/no-extraneous-dependencies */
/* global jest, test, describe, beforeEach, it, expect, setTimeout, document */

import React from 'react';
import { render } from '@testing-library/react';
import { Component as TagField } from '../TagField';

const makeMockSelectComponent = (componentName) => ({ isClearable }) => (
  <div className={`test-dynamic ${componentName}`} data-is-clearable={String(isClearable)}/>
);

function makeProps(obj = {}) {
  return {
    name: 'Test',
    labelKey: 'label',
    valueKey: 'value',
    lazyLoad: false,
    creatable: false,
    multi: true,
    SelectComponent: makeMockSelectComponent('test-select'),
    CreatableSelectComponent: makeMockSelectComponent('test-creatable-select'),
    AsyncSelectComponent: makeMockSelectComponent('test-async-select'),
    AsyncCreatableSelectComponent: makeMockSelectComponent('test-async-creatable-select'),
    ...obj,
  };
}

test('TagField should render a Select component by default', () => {
  const { container } = render(
    <TagField {...makeProps()}/>
  );
  expect(container.querySelectorAll('.test-dynamic')).toHaveLength(1);
  expect(container.querySelector('.test-select')).not.toBeNull();
});

test('TagField should render a CreatableSelect with creatable option', () => {
  const { container } = render(
    <TagField {...makeProps({
      creatable: true
    })}
    />
  );
  expect(container.querySelectorAll('.test-dynamic')).toHaveLength(1);
  expect(container.querySelector('.test-creatable-select')).not.toBeNull();
});

test('Tagfiled should render an AsyncSelect with lazy load option', () => {
  const { container } = render(
    <TagField {...makeProps({
      lazyLoad: true
    })}
    />
  );
  expect(container.querySelectorAll('.test-dynamic')).toHaveLength(1);
  expect(container.querySelector('.test-async-select')).not.toBeNull();
});

test('Tagfiled should render a AsyncCreatableSelect with lazy load and creatable options', () => {
  const { container } = render(
    <TagField {...makeProps({
      lazyLoad: true,
      creatable: true
    })}
    />
  );
  expect(container.querySelectorAll('.test-dynamic')).toHaveLength(1);
  expect(container.querySelector('.test-async-creatable-select')).not.toBeNull();
});

test('TagField passes isClearable=true when in multi mode with clearable=true', () => {
  const { container } = render(
    <TagField {...makeProps({
      multi: true,
      clearable: true,
    })}
    />
  );
  const selectElement = container.querySelector('.test-select');
  expect(selectElement).not.toBeNull();
  expect(selectElement.getAttribute('data-is-clearable')).toBe('true');
});

test('TagField passes isClearable=false when in multi mode with clearable=false', () => {
  const { container } = render(
    <TagField {...makeProps({
      multi: true,
      clearable: false,
    })}
    />
  );
  const selectElement = container.querySelector('.test-select');
  expect(selectElement).not.toBeNull();
  expect(selectElement.getAttribute('data-is-clearable')).toBe('false');
});

test('TagField passes isClearable=true when in non-multi mode with clearable=true', () => {
  const { container } = render(
    <TagField {...makeProps({
      multi: false,
      clearable: true,
    })}
    />
  );
  const selectElement = container.querySelector('.test-select');
  expect(selectElement).not.toBeNull();
  expect(selectElement.getAttribute('data-is-clearable')).toBe('true');
});

test('TagField passes isClearable=false when in non-multi mode with clearable=false', () => {
  const { container } = render(
    <TagField {...makeProps({
      multi: false,
      clearable: false,
    })}
    />
  );
  const selectElement = container.querySelector('.test-select');
  expect(selectElement).not.toBeNull();
  expect(selectElement.getAttribute('data-is-clearable')).toBe('false');
});

test('TagField in non-multi mode shows clear button when clearable=true', () => {
  const onChange = jest.fn();
  const testValue = { label: 'Test Option', value: 'test-value' };
  const { container } = render(
    <TagField {...makeProps({
      multi: false,
      clearable: true,
      value: testValue,
      onChange,
    })}
    />
  );
  const selectElement = container.querySelector('.test-select');
  expect(selectElement).not.toBeNull();
  expect(selectElement.getAttribute('data-is-clearable')).toBe('true');
});

test('TagField in multi mode shows clear button when clearable=true', () => {
  const testValues = [
    { label: 'Option 1', value: 'value-1' },
    { label: 'Option 2', value: 'value-2' }
  ];
  const { container } = render(
    <TagField {...makeProps({
      multi: true,
      clearable: true,
      value: testValues,
    })}
    />
  );
  const selectElement = container.querySelector('.test-select');
  expect(selectElement).not.toBeNull();
  expect(selectElement.getAttribute('data-is-clearable')).toBe('true');
});

test('TagField with value and onChange behaves as controlled component', () => {
  const onChange = jest.fn();
  const testValue = { label: 'Test', value: 'test' };
  const { rerender } = render(
    <TagField {...makeProps({
      multi: false,
      clearable: true,
      value: testValue,
      onChange,
    })}
    />
  );
  expect(onChange).not.toHaveBeenCalled();
  const newValue = { label: 'New', value: 'new' };
  rerender(
    <TagField {...makeProps({
      multi: false,
      clearable: true,
      value: newValue,
      onChange,
    })}
    />
  );
  expect(onChange).not.toHaveBeenCalled();
});
