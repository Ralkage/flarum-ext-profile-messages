import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserPage from 'flarum/forum/components/UserPage';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import LinkButton from 'flarum/common/components/LinkButton';
import Switch from 'flarum/common/components/Switch';
import ProfileMessage from './models/ProfileMessage';
import ProfileMessagesUserPage from './components/ProfileMessagesUserPage';
import NewProfileMessageNotification from './components/NewProfileMessageNotification';

app.initializers.add('ralkage/profile-messages', () => {
  app.store.models['profile-messages'] = ProfileMessage;

  app.routes['user.profileMessages'] = {
    path: '/u/:username/profile-messages',
    component: ProfileMessagesUserPage,
  };

  app.notificationComponents.newProfileMessage = NewProfileMessageNotification;

  // Add Profile Messages nav item to user profile sidebar
  extend(UserPage.prototype, 'navItems', function (items) {
    const user = this.user;
    if (!user) return;

    // Don't show if user has blocked profile messages (unless viewing own profile)
    const actor = app.session.user;
    if (user.attribute('blockProfileMessages') && (!actor || actor.id() !== user.id())) return;

    items.add(
      'profileMessages',
      <LinkButton
        href={app.route('user.profileMessages', { username: user.slug() })}
        icon="fas fa-comment-dots"
      >
        {app.translator.trans('ralkage-profile-messages.forum.user.messages_link')}
      </LinkButton>,
      80
    );
  });

  // Redirect to profile messages if user has it set as default view
  let lastUserPageRoute = null;

  extend(UserPage.prototype, 'show', function (returnValue, user) {
    if (!user) return;

    const currentPath = m.route.get();
    const userPath = app.route('user', { username: user.slug() });
    const prevPath = lastUserPageRoute;
    lastUserPageRoute = currentPath;

    // Only redirect if on the exact user path, and not when switching tabs from a profile sub-page
    const comingFromProfileSubPage = prevPath && prevPath.startsWith('/u/' + user.slug() + '/');

    if (currentPath === userPath && !comingFromProfileSubPage && user.attribute('profileMessagesDefault') && !user.attribute('blockProfileMessages')) {
      m.route.set(app.route('user.profileMessages', { username: user.slug() }), { replace: true });
    }
  });

  // Add privacy settings
  extend(SettingsPage.prototype, 'privacyItems', function (items) {
    items.add(
      'blockProfileMessages',
      <Switch
        state={this.user.preferences().blockProfileMessages}
        onchange={(value) => {
          this.user.savePreferences({ blockProfileMessages: value });
        }}
      >
        {app.translator.trans('ralkage-profile-messages.forum.settings.block_profile_messages_label')}
      </Switch>,
      -10
    );
  });

  // Add default view toggle to privacy settings
  extend(SettingsPage.prototype, 'privacyItems', function (items) {
    items.add(
      'profileMessagesDefault',
      <Switch
        state={this.user.preferences().profileMessagesDefault}
        onchange={(value) => {
          this.user.savePreferences({ profileMessagesDefault: value });
        }}
      >
        {app.translator.trans('ralkage-profile-messages.forum.settings.default_view_label')}
      </Switch>,
      -20
    );
  });
});
