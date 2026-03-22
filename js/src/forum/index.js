import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import LinkButton from 'flarum/common/components/LinkButton';
import Switch from 'flarum/common/components/Switch';
import ProfileMessage from './models/ProfileMessage';
import NewProfileMessageNotification from './components/NewProfileMessageNotification';

export { default as extend } from './extend';

app.initializers.add('ralkage/profile-messages', () => {
  app.store.models['profile-messages'] = ProfileMessage;

  app.notificationComponents.newProfileMessage = NewProfileMessageNotification;

  // Add Profile Messages nav item to user profile sidebar
  extend('flarum/forum/components/UserPage', 'navItems', function (items) {
    const user = this.user;
    if (!user) return;

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

  // Add privacy settings
  extend('flarum/forum/components/SettingsPage', 'privacyItems', function (items) {
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
