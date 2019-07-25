import React from 'react';
import { storiesOf } from '@storybook/react';
import { Component as TagField } from '../TagField';

storiesOf('TagField/TagField', module)
  .addDecorator(storyFn => (
    <div style={{ width: '250px' }} className="ss-tag-field">
      {storyFn()}
    </div>
  ))
  .add('Simple Example', () => (
    <TagField
      name="test"
      options={[
        { Title: 'One', Value: 1 },
        { Title: 'Two', Value: 2 },
        { Title: 'Three', Value: 3 },
        { Title: 'Four', Value: 4 },
        { Title: 'Five', Value: 5 },
      ]}
    />
  ))
  .add('Multiple Selection', () => (
    <TagField
      name="test"
      multi
      options={[
        { Title: 'One', Value: 1 },
        { Title: 'Two', Value: 2 },
        { Title: 'Three', Value: 3 },
        { Title: 'Four', Value: 4 },
        { Title: 'Five', Value: 5 },
      ]}
    />
  ))
;
