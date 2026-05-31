import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';

export default class NewProfileMessageNotification extends Notification {
  icon() {
    return 'fas fa-comment-dots';
  }

  href() {
    const notification = this.attrs.notification;
    const data = notification.content() || {};
    const ownerSlug = data.profileOwnerUsername;
    const fallbackUser = app.session.user;

    if (ownerSlug) {
      return app.route('user.profileMessages', { username: ownerSlug });
    }

    if (fallbackUser) {
      return app.route('user.profileMessages', { username: fallbackUser.slug() });
    }

    return '';
  }

  content() {
    const notification = this.attrs.notification;
    const fromUser = notification.fromUser();
    const data = notification.content() || {};
    const actor = app.session.user;
    const recipientIsOwner = !data.profileOwnerId || (actor && String(actor.id()) === String(data.profileOwnerId));

    const username = fromUser ? fromUser.displayName() : '[deleted]';

    if (!recipientIsOwner) {
      return app.translator.trans('ralkage-profile-messages.forum.notification.new_profile_message_reply_text', {
        username,
        profileOwner: data.profileOwnerDisplayName || data.profileOwnerUsername || '',
      });
    }

    return app.translator.trans('ralkage-profile-messages.forum.notification.new_profile_message_text', {
      username,
    });
  }

  excerpt() {
    return '';
  }
}
