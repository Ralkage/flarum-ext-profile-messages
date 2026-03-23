# Profile Messages

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Public profile wall messages for [Flarum](https://flarum.org), similar to XenForo's visitor messages. Users can post formatted messages on each other's profiles with threaded replies.

<img width="1654" height="1043" alt="image" src="https://github.com/user-attachments/assets/44cde418-f74f-4d57-be3d-a9b86debec60" />

## Features

### Messaging
- **Profile wall messages** — Post public messages on any user's profile page
- **Threaded replies** — Reply to messages with expandable reply threads
- **Rich content** — Full Flarum composer with markdown, BBCode, emoji, and media support
- **Composer preview** — Preview rendered content before posting via the eye icon

### Moderation
- **Report system** — Report messages as inappropriate, spam, harassment, or other with detail text
- **Edit messages** — Authors can edit their own; admins/mods can edit any (permission-based)
- **Delete messages** — Authors, profile owners, and admins can delete messages
- **Report count badge** — Admins see report count on flagged messages

### Profile Settings
- **Disable profile messages** — Users can block messages on their profile (Privacy settings)
- **Default profile view** — Users can set Profile Messages as their default profile tab (Privacy settings)

### Other
- **Permalinks** — Click the timestamp to see full date and a copyable permalink
- **In-app notifications** — Profile owners are notified when someone posts on their profile
- **Pagination** — Messages load 20 at a time with "Load More"
- **Guest-visible** — Profile messages are visible to guests (posting requires login)

## Installation

```bash
composer require ralkage/flarum-ext-profile-messages
php flarum migrate
php flarum cache:clear
```

Enable the extension in the admin panel under **Extensions > Profile Messages**.

## Permissions

Configure in the admin panel under the extension's permissions tab:

| Permission | Category | Description |
|---|---|---|
| Post profile messages | Reply | Allow users to post messages on profiles |
| Delete own profile messages | Reply | Allow users to delete their own messages |
| Edit any profile message | Moderate | Allow moderators to edit any message |

Profile owners can always delete any message on their own profile.

## User Settings

Users can configure these in **Settings > Privacy**:

| Setting | Description |
|---|---|
| Disable profile messages from other users | Blocks all messages on your profile |
| Show Profile Messages as default profile view | Visitors see messages tab first on your profile |

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/profile-messages` | List messages (filter by user, parent) |
| `GET` | `/api/profile-messages/{id}` | Show single message |
| `POST` | `/api/profile-messages` | Create message or reply |
| `PATCH` | `/api/profile-messages/{id}` | Edit message |
| `DELETE` | `/api/profile-messages/{id}` | Delete message |
| `POST` | `/api/profile-messages/preview` | Preview formatted content |
| `POST` | `/api/profile-message-reports` | Report a message |

## Requirements

- Flarum `^1.8`
- PHP 8.0+

## Links

- [Ralkage](https://ralkage.com)
- [GitHub](https://github.com/Ralkage/flarum-ext-profile-messages)
- [Packagist](https://packagist.org/packages/ralkage/flarum-ext-profile-messages)

## License

MIT License. See [LICENSE](LICENSE) for details.
