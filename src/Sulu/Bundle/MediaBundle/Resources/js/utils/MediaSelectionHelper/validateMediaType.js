// @flow

export default function validateMediaType(name: ?string | number): boolean %checks {
    return name === 'audio'
        || name === 'document'
        || name === 'image'
        || name === 'video';
}
