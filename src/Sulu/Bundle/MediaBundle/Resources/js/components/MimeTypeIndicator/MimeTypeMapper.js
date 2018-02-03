// @flow

const mapping = {
    // images
    'image': {
        icon: 'file-image-o',
        backgroundColor: '#f67e00',
    },

    // audio
    'audio': {
        icon: 'file-audio-o',
        backgroundColor: '#f67e00',
    },

    // video
    'video': {
        icon: 'file-video-o',
        backgroundColor: '#f67e00',
    },

    // text
    'text': {
        icon: 'file-text-o',
        backgroundColor: '#585858',
    },

    // documents
    'application/pdf': {
        icon: 'file-pdf-o',
        backgroundColor: '#bb0806',
    },
    'text/plain': {
        icon: 'file-text-o',
        backgroundColor: '#585858',
    },
    'text/rtf': {
        icon: 'file-text-o',
        backgroundColor: '#585858',
    },
    'application/rtf': {
        icon: 'file-text-o',
        backgroundColor: '#585858',
    },
    'text/html': {
        icon: 'file-code-o',
        backgroundColor: '#67217a',
    },
    'application/json': {
        icon: 'file-code-o',
        backgroundColor: '#585858',
    },
    'application/msword': {
        icon: 'file-word-o',
        backgroundColor: '#2c5897',
    },
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': {
        icon: 'file-word-o',
        backgroundColor: '#2c5897',
    },
    'application/vnd.ms-excel': {
        icon: 'file-excel-o',
        backgroundColor: '#00723a',
    },
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': {
        icon: 'file-excel-o',
        backgroundColor: '#00723a',
    },
    'application/vnd.ms-powerpoint': {
        icon: 'file-powerpoint-o',
        backgroundColor: '#d14628',
    },
    'application/vnd.openxmlformats-officedocument.presentationml.presentation': {
        icon: 'file-powerpoint-o',
        backgroundColor: '#d14628',
    },

    // archives
    'application/gzip': {
        icon: 'file-archive-o',
        backgroundColor: '#585858',
    },
    'application/zip': {
        icon: 'file-archive-o',
        backgroundColor: '#585858',
    },

    // misc
    'application/octet-stream': {
        icon: 'file-o',
        backgroundColor: '#585858',
    },
};

export default class MimeTypeMapper {
    static get(mimeType: string) {
        const fileType = mimeType.split('/')[0];

        if (mapping[mimeType]) {
            return mapping[mimeType];
        } else if (mapping[fileType]) {
            return mapping[fileType];
        }

        return {
            icon: 'file-o',
            backgroundColor: '#585858',
        };
    }
}
