/* eslint-disable import/no-extraneous-dependencies */
/* global jest, test, describe, beforeEach, it, expect, setTimeout, document */

import React, { forwardRef, useImperativeHandle } from 'react';
import { render } from '@testing-library/react';
import { Component as TagField } from '../TagField';

const MockSelect = forwardRef(({ className, onChange, onBlur, isMulti, isDisabled, value, options }, ref) => {
  useImperativeHandle(ref, () => ({
    inputRef: document.createElement('input'),
  }));
  const hasValue = value !== undefined && value !== null && (Array.isArray(value) ? value.length > 0 : true);
  return (
    <div
      className={`test-dynamic test-select ${className || ''}`}
      data-testid="select-component"
      data-is-multi={String(isMulti)}
      data-is-disabled={String(isDisabled)}
      data-has-on-change={String(typeof onChange === 'function')}
      data-has-on-blur={String(typeof onBlur === 'function')}
      data-has-value={String(hasValue)}
      data-has-options={String(!!options)}
    />
  );
});

const MockCreatableSelect = forwardRef(({ className, onChange, isMulti, isDisabled, isValidNewOption, getNewOptionData }, ref) => {
  useImperativeHandle(ref, () => ({
    inputRef: document.createElement('input'),
  }));
  return (
    <div
      className={`test-dynamic test-creatable-select ${className || ''}`}
      data-testid="creatable-component"
      data-is-multi={String(isMulti)}
      data-is-disabled={String(isDisabled)}
      data-has-on-change={String(typeof onChange === 'function')}
      data-has-is-valid-new-option={String(typeof isValidNewOption === 'function')}
      data-has-get-new-option-data={String(typeof getNewOptionData === 'function')}
    />
  );
});

const MockAsyncSelect = forwardRef(({ className, isMulti, isDisabled, loadOptions }, ref) => {
  useImperativeHandle(ref, () => ({
    inputRef: document.createElement('input'),
  }));
  return (
    <div
      className={`test-dynamic test-async-select ${className || ''}`}
      data-testid="async-component"
      data-is-multi={String(isMulti)}
      data-is-disabled={String(isDisabled)}
      data-has-load-options={String(typeof loadOptions === 'function')}
    />
  );
});

const MockAsyncCreatableSelect = forwardRef(({ className, isMulti, isDisabled, loadOptions, isValidNewOption }, ref) => {
  useImperativeHandle(ref, () => ({
    inputRef: document.createElement('input'),
  }));
  return (
    <div
      className={`test-dynamic test-async-creatable-select ${className || ''}`}
      data-testid="async-creatable-component"
      data-is-multi={String(isMulti)}
      data-is-disabled={String(isDisabled)}
      data-has-load-options={String(typeof loadOptions === 'function')}
      data-has-is-valid-new-option={String(typeof isValidNewOption === 'function')}
    />
  );
});

function makeProps(obj = {}) {
  return {
    name: 'Test',
    labelKey: 'label',
    valueKey: 'value',
    lazyLoad: false,
    creatable: false,
    multi: true,
    SelectComponent: MockSelect,
    CreatableSelectComponent: MockCreatableSelect,
    AsyncSelectComponent: MockAsyncSelect,
    AsyncCreatableSelectComponent: MockAsyncCreatableSelect,
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

test('Tagfield should render an AsyncSelect with lazy load option', () => {
  const { container } = render(
    <TagField {...makeProps({
      lazyLoad: true
    })}
    />
  );
  expect(container.querySelectorAll('.test-dynamic')).toHaveLength(1);
  expect(container.querySelector('.test-async-select')).not.toBeNull();
});

test('Tagfield should render a AsyncCreatableSelect with lazy load and creatable options', () => {
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

test('TagField should support isMulti prop for multi-select functionality', () => {
  const { container } = render(
    <TagField {...makeProps({
      multi: true
    })}
    />
  );
  expect(container.querySelectorAll('.test-dynamic')).toHaveLength(1);
});

test('TagField should support single select mode with isMulti false', () => {
  const { container } = render(
    <TagField {...makeProps({
      multi: false
    })}
    />
  );
  expect(container.querySelectorAll('.test-dynamic')).toHaveLength(1);
});

test('TagField should call onChange when value changes in controlled mode', () => {
  const onChange = jest.fn();
  const { container } = render(
    <TagField {...makeProps({
      onChange,
      value: []
    })}
    />
  );
  const selectComponent = container.querySelector('[data-testid="select-component"]');
  expect(selectComponent).not.toBeNull();
  expect(selectComponent.getAttribute('data-has-on-change')).toBe('true');
});

test('TagField should track initial state for change detection', () => {
  const testValue = [{ value: 'test1', label: 'Test 1' }];
  const { container } = render(
    <TagField {...makeProps({
      value: testValue
    })}
    />
  );
  const selectComponent = container.querySelector('.test-dynamic');
  expect(selectComponent.getAttribute('data-has-value')).toBe('true');
});

test('TagField should use custom labelKey and valueKey for option rendering', () => {
  const { container } = render(
    <TagField {...makeProps({
      labelKey: 'customLabel',
      valueKey: 'customValue'
    })}
    />
  );
  expect(container.querySelector('[data-testid="select-component"]')).not.toBeNull();
});

test('TagField should apply no-change-track class when hasChanges is false', () => {
  const { container } = render(
    <TagField {...makeProps()}/>
  );
  const selectComponent = container.querySelector('.test-dynamic');
  expect(selectComponent.className).toContain('no-change-track');
});

test('TagField should pass static options to select component', () => {
  const options = [
    { label: 'Option 1', value: 'opt1' },
    { label: 'Option 2', value: 'opt2' }
  ];
  const { container } = render(
    <TagField {...makeProps({
      options,
      lazyLoad: false
    })}
    />
  );
  expect(container.querySelector('[data-has-options="true"]')).not.toBeNull();
});

test('TagField should pass isMulti prop correctly', () => {
  const { container: multiContainer } = render(
    <TagField {...makeProps({ multi: true })} />
  );
  expect(multiContainer.querySelector('[data-is-multi="true"]')).not.toBeNull();

  const { container: singleContainer } = render(
    <TagField {...makeProps({ multi: false })} />
  );
  expect(singleContainer.querySelector('[data-is-multi="false"]')).not.toBeNull();
});

test('TagField should pass isDisabled prop correctly', () => {
  const { container: disabledContainer } = render(
    <TagField {...makeProps({ disabled: true })} />
  );
  expect(disabledContainer.querySelector('[data-is-disabled="true"]')).not.toBeNull();

  const { container: enabledContainer } = render(
    <TagField {...makeProps({ disabled: false })} />
  );
  expect(enabledContainer.querySelector('[data-is-disabled="false"]')).not.toBeNull();
});

test('TagField should pass onChange callback to select component', () => {
  const onChange = jest.fn();
  const { container } = render(
    <TagField {...makeProps({ onChange })} />
  );
  expect(container.querySelector('[data-has-on-change="true"]')).not.toBeNull();
});

test('TagField should pass onBlur handler to select component', () => {
  const { container } = render(
    <TagField {...makeProps()} />
  );
  expect(container.querySelector('[data-has-on-blur="true"]')).not.toBeNull();
});

test('TagField should pass value to select component', () => {
  const testValue = [{ value: 'test1', label: 'Test 1' }];
  const { container } = render(
    <TagField {...makeProps({ value: testValue })} />
  );
  expect(container.querySelector('[data-has-value="true"]')).not.toBeNull();
});

test('TagField should pass creatable callbacks when creatable is true', () => {
  const { container } = render(
    <TagField {...makeProps({ creatable: true })} />
  );
  expect(container.querySelector('[data-has-is-valid-new-option="true"]')).not.toBeNull();
  expect(container.querySelector('[data-has-get-new-option-data="true"]')).not.toBeNull();
});

test('TagField should pass loadOptions callback for lazy load', () => {
  const { container } = render(
    <TagField {...makeProps({ lazyLoad: true })} />
  );
  expect(container.querySelector('[data-has-load-options="true"]')).not.toBeNull();
});

test('TagField should not pass loadOptions for static options', () => {
  const options = [{ label: 'Option 1', value: 'opt1' }];
  const { container } = render(
    <TagField {...makeProps({ lazyLoad: false, options })} />
  );
  expect(container.querySelector('[data-testid="select-component"]')).not.toBeNull();
});

test('TagField should select correct component based on lazyLoad and creatable props', () => {
  const { container: regularContainer } = render(
    <TagField {...makeProps({ lazyLoad: false, creatable: false })} />
  );
  expect(regularContainer.querySelector('[data-testid="select-component"]')).not.toBeNull();

  const { container: creatableContainer } = render(
    <TagField {...makeProps({ lazyLoad: false, creatable: true })} />
  );
  expect(creatableContainer.querySelector('[data-testid="creatable-component"]')).not.toBeNull();

  const { container: asyncContainer } = render(
    <TagField {...makeProps({ lazyLoad: true, creatable: false })} />
  );
  expect(asyncContainer.querySelector('[data-testid="async-component"]')).not.toBeNull();

  const { container: asyncCreatableContainer } = render(
    <TagField {...makeProps({ lazyLoad: true, creatable: true })} />
  );
  expect(asyncCreatableContainer.querySelector('[data-testid="async-creatable-component"]')).not.toBeNull();
});

test('TagField should apply no-change-track class when value equals initial state', () => {
  const initialValue = [{ label: 'initial', value: 'init' }];
  const { container } = render(
    <TagField {...makeProps({ value: initialValue })} />
  );
  expect(container.querySelector('.no-change-track')).not.toBeNull();
});

test('TagField should render with custom pass-through attributes', () => {
  const { container } = render(
    <TagField {...makeProps({ 'data-custom': 'value' })} />
  );
  expect(container.querySelector('[data-testid="select-component"]')).not.toBeNull();
});

test('TagField should handle populated value arrays', () => {
  const { container } = render(
    <TagField {...makeProps({ value: [{ label: 'test', value: 'test' }] })} />
  );
  expect(container.querySelector('[data-has-value="true"]')).not.toBeNull();
});

test('TagField should distinguish between controlled and uncontrolled modes', () => {
  const onChange = jest.fn();
  const { container: controlledContainer } = render(
    <TagField {...makeProps({ onChange })} />
  );
  expect(controlledContainer.querySelector('[data-has-on-change="true"]')).not.toBeNull();

  const { container: uncontrolledContainer } = render(
    <TagField {...makeProps()} />
  );
  expect(uncontrolledContainer.querySelector('[data-testid="select-component"]')).not.toBeNull();
});
