import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';

export default class NewProfileMessageNotification extends Notification {
  icon() {
    return 'fas fa-comment-dots';
  }

  href() {
    const notification = this.attrs.notification;
    const user = app.session.user;

    if (user) {
      return app.route('user.profileMessages', { username: user.slug() });
    }

    return '';
  }

  content() {
    const notification = this.attrs.notification;
    const fromUser = notification.fromUser();

    return app.translator.trans('ralkage-profile-messages.forum.notification.new_profile_message_text', {
      username: fromUser ? fromUser.displayName() : '[deleted]',
    });
  }

  excerpt() {
    return '';
  }
}
