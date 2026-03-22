import Extend from 'flarum/common/extenders';
import ProfileMessagesUserPage from './components/ProfileMessagesUserPage';

export default [
  new Extend.Routes()
    .add('user.profileMessages', '/u/:username/profile-messages', ProfileMessagesUserPage),
];
