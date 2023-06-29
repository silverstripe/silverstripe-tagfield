import React from 'react';
import TagField from 'components/TagField';

const options = [
  { Title: 'One', Value: 1 },
  { Title: 'Two', Value: 2 },
  { Title: 'Three', Value: 3 },
  { Title: 'Four', Value: 4 },
  { Title: 'Five', Value: 5 },
];

export default {
  title: 'TagField/TagField',
  component: TagField,
  decorators: [],
  tags: ['autodocs'],
  parameters: {
    docs: {
      description: {
        component: 'The Tag Field component.'
      },
      canvas: {
        sourceState: 'shown',
      },
    }
  },
  argTypes: {
    name: {
      control: 'text',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: null },
      },
    },
    options: {
      description: 'List of tags options',
      control: 'select',
      table: {
        type: { summary: 'string' },
      },
    },
    labelKey: {
      control: 'text',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: 'Title' },
      },
    },
    valueKey: {
      control: 'text',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: 'Value' },
      },
    },
    lazyLoad: {
      control: 'boolean',
      table: {
        type: { summary: 'boolean' },
        defaultValue: { summary: false },
      },
    },
    creatable: {
      control: 'boolean',
      table: {
        type: { summary: 'boolean' },
        defaultValue: { summary: false },
      },
    },
    multi: {
      control: 'boolean',
      table: {
        type: { summary: 'boolean' },
        defaultValue: { summary: false },
      },
    },
    disabled: {
      control: 'boolean',
      table: {
        type: { summary: 'boolean' },
        defaultValue: { summary: false },
      },
    },
    optionUrl: {
      control: 'text',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: null },
      },
    },
  },
  args: {
    name: 'Test',
    options
  }
};

export const SimpleExample = (args) => (
  <TagField
    {...args}
  />
);

export const MultipleSelection = (args) => (
  <TagField
    {...args}
    multi
  />
);
