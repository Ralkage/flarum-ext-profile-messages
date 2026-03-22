import app from 'flarum/forum/app';
import ComposerBody from 'flarum/forum/components/ComposerBody';
import icon from 'flarum/common/helpers/icon';
import username from 'flarum/common/helpers/username';
import Link from 'flarum/common/components/Link';
import extractText from 'flarum/common/utils/extractText';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

export default class ProfileMessageComposer extends ComposerBody {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    const isEdit = !!attrs.editMessage;

    attrs.placeholder = attrs.placeholder || extractText(app.translator.trans('ralkage-profile-messages.forum.composer.placeholder'));
    attrs.submitLabel = attrs.submitLabel || app.translator.trans(
      isEdit ? 'ralkage-profile-messages.forum.composer.save_button' : 'ralkage-profile-messages.forum.composer.submit_button'
    );
    attrs.confirmExit = attrs.confirmExit || extractText(app.translator.trans('ralkage-profile-messages.forum.composer.confirm_exit'));

    if (isEdit) {
      attrs.originalContent = attrs.editMessage.content();
    }
  }

  oninit(vnode) {
    super.oninit(vnode);

    this.previewHtml = '';
    this.previewLoading = false;
    this.showPreview = false;
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

  /**
   * Enable the preview button in the composer toolbar.
   */
  jumpToPreview(e) {
    if (e) e.preventDefault();

    this.showPreview = !this.showPreview;

    if (this.showPreview) {
      this.loadPreview();
    }

    m.redraw();
  }

  loadPreview() {
    const content = this.composer.fields.content();
    if (!content || !content.trim()) {
      this.previewHtml = '';
      return;
    }

    this.previewLoading = true;

    // Use the API to format the content server-side
    app.request({
      method: 'POST',
      url: `${app.forum.attribute('apiUrl')}/profile-messages/preview`,
      body: { content },
    }).then((response) => {
      this.previewHtml = response.contentHtml || '';
      this.previewLoading = false;
      m.redraw();
    }).catch(() => {
      // Fallback: show raw content
      this.previewHtml = '<p>' + content.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
      this.previewLoading = false;
      m.redraw();
    });
  }

  view() {
    const view = super.view();

    // Inject preview panel if active
    if (this.showPreview) {
      const previewPanel = (
        <div className="ProfileMessageComposer-preview">
          <div className="ProfileMessageComposer-previewHeader">
            <strong>{app.translator.trans('ralkage-profile-messages.forum.composer.preview_heading')}</strong>
          </div>
          <div className="ProfileMessageComposer-previewContent">
            {this.previewLoading ? <LoadingIndicator /> : m.trust(this.previewHtml)}
          </div>
        </div>
      );

      // Append preview after the composer body
      if (view && view.children) {
        view.children.push(previewPanel);
      }
    }

    return view;
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
