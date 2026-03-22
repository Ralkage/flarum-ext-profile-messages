import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';

export default class ReportMessageModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    this.reason = 'inappropriate';
    this.reasonDetail = '';
    this.loading = false;
  }

  className() {
    return 'ReportMessageModal Modal--small';
  }

  title() {
    return app.translator.trans('ralkage-profile-messages.forum.report.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            {['inappropriate', 'spam', 'harassment', 'other'].map((reason) => (
              <label className="checkbox" style="display: block; margin-bottom: 8px;">
                <input
                  type="radio"
                  name="reason"
                  checked={this.reason === reason}
                  onchange={() => { this.reason = reason; }}
                  disabled={this.loading}
                />
                {' '}
                {app.translator.trans(`ralkage-profile-messages.forum.report.reason_${reason}`)}
              </label>
            ))}
          </div>

          <div className="Form-group">
            <textarea
              className="FormControl"
              rows="3"
              placeholder={app.translator.trans('ralkage-profile-messages.forum.report.detail_placeholder')}
              value={this.reasonDetail}
              oninput={(e) => { this.reasonDetail = e.target.value; }}
              disabled={this.loading}
            />
          </div>

          <div className="Form-group">
            <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
              {app.translator.trans('ralkage-profile-messages.forum.report.submit_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    app.request({
      method: 'POST',
      url: `${app.forum.attribute('apiUrl')}/profile-message-reports`,
      body: {
        data: {
          attributes: {
            messageId: this.attrs.message.id(),
            reason: this.reason,
            reasonDetail: this.reasonDetail,
          },
        },
      },
    }).then(() => {
      this.loading = false;
      app.alerts.show({ type: 'success' }, app.translator.trans('ralkage-profile-messages.forum.report.success'));
      this.hide();
    }).catch(() => {
      this.loading = false;
      m.redraw();
    });
  }
}
