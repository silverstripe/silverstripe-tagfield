import Injector from 'lib/Injector';
import TagField from '../components/TagField';

export default () => {
  Injector.component.registerMany({
    TagField,
  });
};
