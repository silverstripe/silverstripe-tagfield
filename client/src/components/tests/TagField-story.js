import React from 'react';
import TagField from 'components/TagField';

export default {
    title: 'TagField/TagField',

    decorators: [
        (storyFn) => (
            <div style={{ width: '250px' }} className="ss-tag-field">
                {storyFn()}
            </div>
        ),
    ],
};

export const SimpleExample = () => (
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
);

export const MultipleSelection = () => (
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
);
