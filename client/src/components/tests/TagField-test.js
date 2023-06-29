/* eslint-disable import/no-extraneous-dependencies */
/* global jest, test, describe, beforeEach, it, expect, setTimeout, document */

import React from 'react';
import { render } from '@testing-library/react';
import { Component as TagField } from '../TagField';

function makeProps(obj = {}) {
  return {
    name: 'Test',
    labelKey: 'label',
    valueKey: 'value',
    lazyLoad: false,
    creatable: false,
    multi: true,
    SelectComponent: () => <div className="test-dynamic test-select" />,
    CreatableSelectComponent: () => <div className="test-dynamic test-creatable-select" />,
    AsyncSelectComponent: () => <div className="test-dynamic test-async-select" />,
    AsyncCreatableSelectComponent: () => <div className="test-dynamic test-async-creatable-select" />,
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
