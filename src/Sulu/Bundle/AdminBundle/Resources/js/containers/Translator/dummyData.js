// @flow

export const DUMMY_TEXT = "Welcome to our website. We offer premium services for all your needs. Contact us for more information.";

// Pre-defined segments for demonstration (plain text)
export const DUMMY_SEGMENTS = [
    {
        id: 1,
        text: "Welcome to our website.",
        beginPos: 0,
        endPos: 23
    },
    {
        id: 2,
        text: "We offer premium services for all your needs.",
        beginPos: 24,
        endPos: 69
    },
    {
        id: 3,
        text: "Contact us for more information.",
        beginPos: 70,
        endPos: 104
    }
];

export const DUMMY_HTML_TEXT = "<h2>Welcome to our website.</h2><p>We offer <strong>premium services</strong> for all your needs.</p><p>Contact us for <a href=\"#\">more information</a>.</p>";

// Pre-defined segments for HTML content
export const DUMMY_HTML_SEGMENTS = [
    {
        id: 1,
        text: "<h2>Welcome to our website.</h2>",
        beginPos: 0,
        endPos: 32
    },
    {
        id: 2,
        text: "<p>We offer <strong>premium services</strong> for all your needs.</p>",
        beginPos: 32,
        endPos: 101
    },
    {
        id: 3,
        text: "<p>Contact us for <a href=\"#\">more information</a>.</p>",
        beginPos: 101,
        endPos: 157
    }
];

// Pre-defined alternatives for demonstration
export const DUMMY_ALTERNATIVES = {
    1: [
        "Welcome to our website.",
        "Welcome to our site.",
        "Thanks for visiting our website.",
        "We're glad you're here at our website.",
        "Hello and welcome to our website."
    ],
    2: [
        "We offer premium services for all your needs.",
        "We provide high-quality services for your requirements.",
        "Our premium services fulfill all your needs.",
        "All your needs can be met by our premium services.",
        "For all your needs, we offer top-notch services."
    ],
    3: [
        "Contact us for more information.",
        "Get in touch for further details.",
        "Reach out to us for additional information.",
        "For more details, please contact us.",
        "Need more info? Contact our team."
    ],
    4: [
        "Our dedicated team is ready to assist you.",
        "Our expert team stands ready to help you.",
        "We have a team of specialists ready to support you.",
        "Our professionals are available to provide assistance.",
        "A team of experts is waiting to help you."
    ]
};

// HTML alternatives with formatting preserved
export const DUMMY_HTML_ALTERNATIVES = {
    1: [
        "<h2>Welcome to our website.</h2>",
        "<h2>Welcome to our site.</h2>",
        "<h2>Thanks for visiting our website.</h2>",
        "<h2>We're glad you're here at our website.</h2>",
        "<h2>Hello and welcome to our website.</h2>"
    ],
    2: [
        "<p>We offer <strong>premium services</strong> for all your needs.</p>",
        "<p>We provide <strong>high-quality services</strong> for your requirements.</p>",
        "<p>Our <strong>premium services</strong> fulfill all your needs.</p>",
        "<p>All your needs can be met by our <strong>premium services</strong>.</p>",
        "<p>For all your needs, we offer <strong>top-notch services</strong>.</p>"
    ],
    3: [
        "<p>Contact us for <a href=\"#\">more information</a>.</p>",
        "<p>Get in touch for <a href=\"#\">further details</a>.</p>",
        "<p>Reach out to us for <a href=\"#\">additional information</a>.</p>",
        "<p>For <a href=\"#\">more details</a>, please contact us.</p>",
        "<p>Need <a href=\"#\">more info</a>? Contact our team.</p>"
    ],
};
