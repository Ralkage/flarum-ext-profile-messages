import Model from 'flarum/common/Model';

export default class ProfileMessage extends Model {
  content() {
    return Model.attribute('content').call(this);
  }
  contentHtml() {
    return Model.attribute('contentHtml').call(this);
  }
  parentId() {
    return Model.attribute('parentId').call(this);
  }
  replyCount() {
    return Model.attribute('replyCount').call(this);
  }
  createdAt() {
    return Model.attribute('createdAt', Model.transformDate).call(this);
  }
  canDelete() {
    return Model.attribute('canDelete').call(this);
  }
  canEdit() {
    return Model.attribute('canEdit').call(this);
  }
  canReply() {
    return Model.attribute('canReply').call(this);
  }
  canReport() {
    return Model.attribute('canReport').call(this);
  }
  reportCount() {
    return Model.attribute('reportCount').call(this);
  }
  user() {
    return Model.hasOne('user').call(this);
  }
  author() {
    return Model.hasOne('author').call(this);
  }
}
