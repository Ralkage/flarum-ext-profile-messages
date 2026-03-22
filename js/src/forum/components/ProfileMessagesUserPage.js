import app from 'flarum/forum/app';
import UserPage from 'flarum/forum/components/UserPage';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';
import Tooltip from 'flarum/common/components/Tooltip';
import Avatar from 'flarum/common/components/Avatar';
import username from 'flarum/common/helpers/username';
import humanTime from 'flarum/common/helpers/humanTime';
import fullTime from 'flarum/common/helpers/fullTime';
import Link from 'flarum/common/components/Link';
import getProfileMessageComposer from './ProfileMessageComposer';
import ReportMessageModal from './ReportMessageModal';

export default class ProfileMessagesUserPage extends UserPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = true;
    this.moreResults = false;
    this.messages = [];

    // Expanded replies
    this.expandedReplies = {};
    this.repliesData = {};
    this.repliesLoading = {};

    this.loadUser(m.route.param('username'));
  }

  show(user) {
    super.show(user);

    // If profile messages are blocked, redirect to default posts view
    const actor = app.session.user;
    if (user.attribute('blockProfileMessages') && (!actor || actor.id() !== user.id())) {
      m.route.set(app.route('user', { username: user.slug() }));
      return;
    }

    this.refresh();
  }

  content() {
    return (
      <div className="ProfileMessagesUserPage">
        {this.postMessageButton()}

        {this.loading ? (
          <LoadingIndicator />
        ) : this.messages.length === 0 ? (
          <div className="ProfileMessages-empty">
            <p>{app.translator.trans('ralkage-profile-messages.forum.user.messages_empty_text')}</p>
          </div>
        ) : (
          <div className="ProfileMessages-list">
            {this.messages.map((msg) => this.messageItem(msg))}

            {this.moreResults && (
              <div className="ProfileMessages-loadMore">
                <Button className="Button" onclick={() => this.loadMore()}>
                  {app.translator.trans('ralkage-profile-messages.forum.user.messages_load_more_button')}
                </Button>
              </div>
            )}
          </div>
        )}
      </div>
    );
  }

  postMessageButton() {
    const actor = app.session.user;
    if (!actor || !actor.attribute('canPostProfileMessages')) return '';

    const user = this.user;
    if (user && user.attribute('blockProfileMessages') && actor.id() !== user.id()) return '';

    return (
      <div className="ProfileMessages-compose" style="margin-bottom: 20px;">
        <Button
          className="Button Button--primary"
          icon="fas fa-comment-dots"
          onclick={() => this.openComposer()}
        >
          {app.translator.trans('ralkage-profile-messages.forum.composer.submit_button')}
        </Button>
      </div>
    );
  }

  async openComposer(parentId) {
    const component = await getProfileMessageComposer();
    const user = this.user;

    app.composer.load(component, {
      user,
      parentId: parentId || null,
      onsubmit: (msg) => {
        if (parentId) {
          // Add reply
          if (this.repliesData[parentId]) {
            this.repliesData[parentId].push(msg);
          }
          const parent = this.messages.find((m) => m.id() === String(parentId));
          if (parent) {
            parent.pushAttributes({ replyCount: (parent.replyCount() || 0) + 1 });
          }
          this.expandedReplies[parentId] = true;
          if (!this.repliesData[parentId]) {
            this.loadReplies(parentId);
          }
        } else {
          // Add top-level message
          this.messages.unshift(msg);
        }
        m.redraw();
      },
    });

    app.composer.show();
  }

  messageItem(msg) {
    const author = msg.author();
    const replyCount = msg.replyCount();
    const msgId = msg.id();
    const isExpanded = this.expandedReplies[msgId];

    return (
      <div className="ProfileMessage ProfileMessage--toplevel" key={msgId}>
        <div className="ProfileMessage-main">
          <div className="ProfileMessage-avatar">
            {author ? (
              <Link href={app.route.user(author)}><Avatar user={author} /></Link>
            ) : (
              <Avatar user={null} />
            )}
          </div>
          <div className="ProfileMessage-body">
            <div className="ProfileMessage-header">
              <span className="ProfileMessage-author">
                {author ? (
                  <Link href={app.route.user(author)}>{username(author)}</Link>
                ) : (
                  '[deleted]'
                )}
              </span>
              {this.messageMeta(msg)}
              <span className="ProfileMessage-actions">
                {this.messageActions(msg)}
              </span>
            </div>
            <div className="ProfileMessage-content">{m.trust(msg.contentHtml())}</div>
            {msg.reportCount() > 0 && (
              <div className="ProfileMessage-reportBadge">
                <i className="fas fa-flag" /> {msg.reportCount()} {msg.reportCount() === 1 ? 'report' : 'reports'}
              </div>
            )}

            {replyCount > 0 && (
              <Button
                className="Button Button--link ProfileMessage-toggleReplies"
                onclick={() => this.toggleReplies(msgId)}
              >
                {isExpanded
                  ? app.translator.trans('ralkage-profile-messages.forum.message.hide_replies')
                  : app.translator.trans('ralkage-profile-messages.forum.message.show_replies', { count: replyCount })}
              </Button>
            )}
          </div>
        </div>

        {/* Replies */}
        {isExpanded && (
          <div className="ProfileMessage-replies">
            {this.repliesLoading[msgId] ? (
              <LoadingIndicator />
            ) : (
              (this.repliesData[msgId] || []).map((reply) => this.replyItem(reply))
            )}
          </div>
        )}
      </div>
    );
  }

  replyItem(reply) {
    const author = reply.author();

    return (
      <div className="ProfileMessage ProfileMessage--reply" key={reply.id()}>
        <div className="ProfileMessage-avatar ProfileMessage-avatar--small">
          {author ? (
            <Link href={app.route.user(author)}><Avatar user={author} /></Link>
          ) : (
            <Avatar user={null} />
          )}
        </div>
        <div className="ProfileMessage-body">
          <div className="ProfileMessage-header">
            <span className="ProfileMessage-author">
              {author ? (
                <Link href={app.route.user(author)}>{username(author)}</Link>
              ) : (
                '[deleted]'
              )}
            </span>
            {this.messageMeta(reply)}
            <span className="ProfileMessage-actions">
              {this.messageActions(reply, true)}
            </span>
          </div>
          <div className="ProfileMessage-content">{m.trust(reply.contentHtml())}</div>
        </div>
      </div>
    );
  }

  // --- Actions ---

  messageActions(msg, isReply) {
    return [
      !isReply && msg.canReply() && (
        <Tooltip text={app.translator.trans('ralkage-profile-messages.forum.message.reply_button')}>
          <Button className="Button Button--link ProfileMessage-action" icon="fas fa-reply" onclick={() => this.openComposer(msg.id())} />
        </Tooltip>
      ),
      msg.canEdit() && (
        <Tooltip text={app.translator.trans('ralkage-profile-messages.forum.message.edit_button')}>
          <Button className="Button Button--link ProfileMessage-action" icon="fas fa-pencil-alt" onclick={() => this.editMessage(msg)} />
        </Tooltip>
      ),
      msg.canReport() && (
        <Tooltip text={app.translator.trans('ralkage-profile-messages.forum.message.report_button')}>
          <Button className="Button Button--link ProfileMessage-action" icon="fas fa-flag" onclick={() => this.reportMessage(msg)} />
        </Tooltip>
      ),
      msg.canDelete() && (
        <Tooltip text={app.translator.trans('ralkage-profile-messages.forum.message.delete_button')}>
          <Button className="Button Button--link ProfileMessage-action ProfileMessage-action--danger" icon="fas fa-trash" onclick={() => isReply ? this.deleteReply(msg) : this.deleteMessage(msg)} />
        </Tooltip>
      ),
    ].filter(Boolean);
  }

  messageMeta(msg) {
    const permalink = `${app.forum.attribute('baseUrl')}/profile-message/${msg.id()}`;
    const touch = 'ontouchstart' in document.documentElement;

    return (
      <div className="Dropdown ProfileMessage-meta">
        <a className="Dropdown-toggle ProfileMessage-time" data-toggle="dropdown" onclick={(e) => {
          setTimeout(() => {
            const el = e.target.closest('.Dropdown').querySelector('.ProfileMessage-permalink');
            if (el && el.select) el.select();
          });
          e.redraw = false;
        }}>
          {humanTime(msg.createdAt())}
        </a>
        <div className="Dropdown-menu dropdown-menu">
          <span className="ProfileMessage-fullTime">{fullTime(msg.createdAt())}</span>
          {touch ? (
            <a className="Button ProfileMessage-permalink" href={permalink}>{permalink}</a>
          ) : (
            <input className="FormControl ProfileMessage-permalink" value={permalink} onclick={(e) => e.stopPropagation()} />
          )}
        </div>
      </div>
    );
  }

  async editMessage(msg) {
    app.composer.load(await getProfileMessageComposer(), {
      user: this.user,
      editMessage: msg,
      onsubmit: (updated) => {
        // The message will be updated in the store automatically
        m.redraw();
      },
    });
    app.composer.show();
  }

  reportMessage(msg) {
    app.modal.show(ReportMessageModal, { message: msg });
  }

  // --- Data ---

  refresh() {
    this.loading = true;
    this.messages = [];
    m.redraw();

    this.loadResults(0).then(() => {
      this.loading = false;
      m.redraw();
    });
  }

  loadResults(offset) {
    return app.store
      .find('profile-messages', {
        filter: { user: this.user.id() },
        page: { offset, limit: 20 },
        sort: '-createdAt',
      })
      .then((results) => {
        this.messages.push(...results);
        this.moreResults = results.payload && results.payload.links && results.payload.links.next;
        return results;
      });
  }

  loadMore() {
    this.loadResults(this.messages.length).then(() => m.redraw());
  }

  toggleReplies(msgId) {
    if (this.expandedReplies[msgId]) {
      this.expandedReplies[msgId] = false;
    } else {
      this.expandedReplies[msgId] = true;
      if (!this.repliesData[msgId]) {
        this.loadReplies(msgId);
      }
    }
  }

  loadReplies(parentId) {
    this.repliesLoading[parentId] = true;
    m.redraw();

    app.store
      .find('profile-messages', {
        filter: { user: this.user.id(), parent: parentId },
        page: { limit: 50 },
      })
      .then((results) => {
        this.repliesData[parentId] = results;
        this.repliesLoading[parentId] = false;
        m.redraw();
      });
  }

  deleteMessage(msg) {
    if (!confirm(app.translator.trans('ralkage-profile-messages.forum.message.delete_confirm'))) return;

    msg.delete().then(() => {
      this.messages = this.messages.filter((m) => m.id() !== msg.id());
      m.redraw();
    });
  }

  deleteReply(reply) {
    if (!confirm(app.translator.trans('ralkage-profile-messages.forum.message.delete_confirm'))) return;

    const parentId = reply.parentId();

    reply.delete().then(() => {
      if (this.repliesData[parentId]) {
        this.repliesData[parentId] = this.repliesData[parentId].filter((r) => r.id() !== reply.id());
      }

      const parent = this.messages.find((m) => m.id() === String(parentId));
      if (parent) {
        parent.pushAttributes({ replyCount: Math.max(0, (parent.replyCount() || 0) - 1) });
      }

      m.redraw();
    });
  }
}
