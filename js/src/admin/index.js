import app from 'flarum/admin/app';

app.initializers.add('ralkage/profile-messages', () => {
  app.extensionData
    .for('ralkage-profile-messages')
    .registerPermission(
      {
        icon: 'fas fa-comment-dots',
        label: app.translator.trans('ralkage-profile-messages.admin.permissions.post_label'),
        permission: 'profileMessage.post',
      },
      'reply',
      95
    )
    .registerPermission(
      {
        icon: 'fas fa-trash',
        label: app.translator.trans('ralkage-profile-messages.admin.permissions.delete_own_label'),
        permission: 'profileMessage.deleteOwn',
      },
      'reply',
      94
    )
    .registerPermission(
      {
        icon: 'fas fa-pencil-alt',
        label: app.translator.trans('ralkage-profile-messages.admin.permissions.edit_any_label'),
        permission: 'profileMessage.editAny',
      },
      'moderate',
      95
    );
});
