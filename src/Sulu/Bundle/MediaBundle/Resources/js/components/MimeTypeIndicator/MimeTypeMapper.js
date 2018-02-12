// @flow

const mapping = {
    // images
    'image': {
        icon: 'fa-file-image-o',
        backgroundColor: '#f67e00',
    },

    // audio
    'audio': {
        icon: 'fa-file-audio-o',
        backgroundColor: '#f67e00',
    },

    // video
    'video': {
        icon: 'fa-file-video-o',
        backgroundColor: '#f67e00',
    },

    // text
    'text': {
        icon: 'fa-file-text-o',
        backgroundColor: '#585858',
    },

    // documents
    'application/pdf': {
        icon: 'fa-file-pdf-o',
        backgroundColor: '#bb0806',
    },
    'text/plain': {
        icon: 'fa-file-text-o',
        backgroundColor: '#585858',
    },
    'text/rtf': {
        icon: 'fa-file-text-o',
        backgroundColor: '#585858',
    },
    'application/rtf': {
        icon: 'fa-file-text-o',
        backgroundColor: '#585858',
    },
    'text/html': {
        icon: 'fa-file-code-o',
        backgroundColor: '#67217a',
    },
    'application/json': {
        icon: 'fa-file-code-o',
        backgroundColor: '#585858',
    },
    'application/msword': {
        icon: 'fa-file-word-o',
        backgroundColor: '#2c5897',
    },
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': {
        icon: 'fa-file-word-o',
        backgroundColor: '#2c5897',
    },
    'application/vnd.ms-excel': {
        icon: 'fa-file-excel-o',
        backgroundColor: '#00723a',
    },
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': {
        icon: 'fa-file-excel-o',
        backgroundColor: '#00723a',
    },
    'application/vnd.ms-powerpoint': {
        icon: 'fa-file-powerpoint-o',
        backgroundColor: '#d14628',
    },
    'application/vnd.openxmlformats-officedocument.presentationml.presentation': {
        icon: 'fa-file-powerpoint-o',
        backgroundColor: '#d14628',
    },

    // archives
    'application/gzip': {
        icon: 'fa-file-archive-o',
        backgroundColor: '#585858',
    },
    'application/zip': {
        icon: 'fa-file-archive-o',
        backgroundColor: '#585858',
    },

    // misc
    'application/octet-stream': {
        icon: 'fa-file-o',
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
            icon: 'fa-file-o',
            backgroundColor: '#585858',
        };
    }
}
