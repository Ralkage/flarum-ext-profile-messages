import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

export default [
  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-comment-dots',
        label: app.translator.trans('ralkage-profile-messages.admin.permissions.post_label'),
        permission: 'profileMessage.post',
      }),
      'reply',
      95
    )
    .permission(
      () => ({
        icon: 'fas fa-trash',
        label: app.translator.trans('ralkage-profile-messages.admin.permissions.delete_own_label'),
        permission: 'profileMessage.deleteOwn',
      }),
      'reply',
      94
    )
    .permission(
      () => ({
        icon: 'fas fa-pencil-alt',
        label: app.translator.trans('ralkage-profile-messages.admin.permissions.edit_any_label'),
        permission: 'profileMessage.editAny',
      }),
      'moderate',
      95
    ),
];
