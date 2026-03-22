import app from 'flarum/forum/app';
import ComposerBody from 'flarum/forum/components/ComposerBody';
import icon from 'flarum/common/helpers/icon';
import username from 'flarum/common/helpers/username';
import Link from 'flarum/common/components/Link';
import extractText from 'flarum/common/utils/extractText';

export default class ProfileMessageComposer extends ComposerBody {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    const isEdit = !!attrs.editMessage;

    attrs.placeholder = attrs.placeholder || extractText(app.translator.trans('ralkage-profile-messages.forum.composer.placeholder'));
    attrs.submitLabel = attrs.submitLabel || app.translator.trans(
      isEdit ? 'ralkage-profile-messages.forum.composer.save_button' : 'ralkage-profile-messages.forum.composer.submit_button'
    );
    attrs.confirmExit = attrs.confirmExit || extractText(app.translator.trans('ralkage-profile-messages.forum.composer.confirm_exit'));

    // Pre-fill content when editing
    if (isEdit) {
      // The stored content is XML from the formatter - we need the raw text
      // Use unparse to get the original markdown/text back
      attrs.originalContent = attrs.editMessage.content();
    }
  }

  headerItems() {
    const items = super.headerItems();
    const user = this.attrs.user;
    const isEdit = !!this.attrs.editMessage;

    let heading;
    let iconName;

    if (isEdit) {
      iconName = 'fas fa-pencil-alt';
      heading = app.translator.trans('ralkage-profile-messages.forum.composer.edit_heading');
    } else if (this.attrs.parentId) {
      iconName = 'fas fa-reply';
      heading = app.translator.trans('ralkage-profile-messages.forum.composer.reply_heading', {
        username: <Link href={app.route.user(user)}>{username(user)}</Link>,
      });
    } else {
      iconName = 'fas fa-comment-dots';
      heading = app.translator.trans('ralkage-profile-messages.forum.composer.heading', {
        username: <Link href={app.route.user(user)}>{username(user)}</Link>,
      });
    }

    items.add('title', <h3>{icon(iconName)}{' '}{heading}</h3>);

    return items;
  }

  data() {
    return {
      content: this.composer.fields.content(),
    };
  }

  onsubmit() {
    this.loading = true;
    m.redraw();

    const isEdit = !!this.attrs.editMessage;

    if (isEdit) {
      this.attrs.editMessage
        .save(this.data())
        .then((msg) => {
          if (this.attrs.onsubmit) {
            this.attrs.onsubmit(msg);
          }
          app.alerts.show(
            { type: 'success' },
            app.translator.trans('ralkage-profile-messages.forum.composer.saved_message')
          );
          this.composer.hide();
        }, this.loaded.bind(this));
    } else {
      const data = this.data();
      data.relationships = { user: this.attrs.user };

      if (this.attrs.parentId) {
        data.parentId = this.attrs.parentId;
      }

      app.store
        .createRecord('profile-messages')
        .save(data)
        .then((msg) => {
          if (this.attrs.onsubmit) {
            this.attrs.onsubmit(msg);
          }
          app.alerts.show(
            { type: 'success' },
            app.translator.trans('ralkage-profile-messages.forum.composer.posted_message')
          );
          this.composer.hide();
        }, this.loaded.bind(this));
    }
  }
}
