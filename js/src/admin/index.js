import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('ralkage/profile-messages', () => {
  // Handled by extend.js extenders
});
