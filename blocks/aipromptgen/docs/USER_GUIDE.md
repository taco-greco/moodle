# User Guide - AI Prompt Generator for Moodle

Current release: 1.5.0 (2026-01-31)

## Introduction

The AI Prompt Generator is a Moodle block plugin designed to help teachers create effective AI prompts for their courses. This guide will walk you through all aspects of using the plugin efficiently.

## Getting Started

### Adding the Block to Your Course

1. Turn editing on in your course
2. Click "Add a block"
3. Select "AI Prompt Generator" from the list
4. The block will appear with an "Open Prompt Builder" button

### Basic Navigation

- The block displays competencies and outcomes if configured for your course
- Click "Open Prompt Builder" to access the main form
- Use the Browse buttons next to fields to open selection modals

## Using the Prompt Builder

### Form Fields Overview

1. **Subject**
   - Automatically filled with course name
   - Can be modified for specific topics
   - Helps contextualize the prompt

2. **Student Age/Grade**
   - Enter age directly or use Browse
   - Select single age or range
   - Format: "15" or "15-16"

3. **Topic**
   - Enter teaching topic
   - Use Browse to select from course sections
   - Supports free text and suggestions

4. **Lesson Title**
   - Name your specific lesson
   - Browse course sections/activities
   - Helps focus the prompt

5. **Class Type**
   - Specify teaching format
   - Common options via Browse
   - Examples: lecture, workshop, lab

6. **Number of Classes**
   - Specify how many sessions
   - Minimum value: 1
   - Affects prompt complexity

7. **Lesson Duration**
   - Choose 45 or 60 minutes
   - Helps structure content appropriately

8. **Learning Outcomes**
   - Link to course competencies
   - Include gradebook outcomes
   - Multiple selection supported

9. **Language**
   - Select prompt language
   - Affects generated text
   - Supports language codes

10. **Purpose**
    - Define prompt objective
    - Examples: lesson plan, quiz
    - Browse common purposes

11. **Audience**
    - Specify target reader
    - Teacher or student-facing
    - Affects prompt tone

### Using Browse Modals

1. **Opening Modals**
   - Click Browse button next to field
   - Modal opens with options
   - Search if available

2. **Making Selections**
   - Single or multi-select
   - Click to choose
   - Confirm selection

3. **Custom Entries**
   - Type directly in fields
   - Combine with selections
   - Free text supported

## Working with Competencies and Outcomes

### Viewing Available Items

1. **Competencies**
   - Course-level competencies
   - Activity competencies
   - Displayed in block

2. **Outcomes**
   - Local course outcomes
   - Global outcomes
   - Integration with grades

### Adding to Prompts

1. **Selection Process**
   - Open outcomes modal
   - Multi-select items
   - Review in textarea

2. **Customization**
   - Edit selected text
   - Add custom outcomes
   - Format as needed

## Generating and Using Prompts

### Prompt Generation

1. **Real-time Generation**
   - Fill required fields
   - The prompt updates automatically as you type
   - No need to click a "Generate" button

2. **Output Options**
   - Copy generated text
   - Download as file
   - Send to AI (if configured)

### AI Integration

1. **Provider Selection**
   - Choose OpenAI or Ollama
   - Verify configuration
   - Select model

2. **Sending Prompts**
   - Click "Send to AI"
    - Wait for response (real-time streaming supported for Ollama)
    - View results in the Response Modal

3. **Response View Modes**
    - **RAW**: Displays the exact markdown returned by the AI.
    - **TEXT**: A cleaned-up version with markdown formatting removed, ideal for simple copy-pasting.
    - **HTML**: Shows a rendered preview of the content with proper headings, lists, and formatting.
    - **HTML CODE**: Provides the underlying HTML source code for use in other web editors.

4. **Copying Results**
    - Click "Copy to clipboard" to copy the content of the currently active view.
    - When in **HTML** (Rich) view, the content is copied as **Rich Text**, preserving formatting when pasted into applications like Word, Google Docs, or Moodle's text editor.
    - A status indicator will confirm when the content has been successfully copied.

## Best Practices

## What's New in 1.5.0

- **Dynamic Prompt Generation**: The prompt now updates instantly as you interact with the form, providing a smoother experience.
- **Improved Ollama Support**: Better streaming stability, timeout handling, and immediate access to response actions.
- **Simplified Workflow**: Removed the "Insert into Editor" button to focus on prompt generation and copying.

## What's New in 1.3

- **Refined Response Modal**: New view modes (RAW, TEXT, HTML, HTML CODE).
- **Rich Text Copy**: Preserve formatting when copying from the HTML preview.
- **Real-time Streaming**: Faster feedback when using local providers like Ollama.
- **UI Enhancements**: Added copy status notifications and improved layout stability.

## What's New in 1.2

- RAW / TEXT / HTML view modes for AI response.
- Client-side Markdown rendering.
- Fixed Ollama connectivity for local networks.

## What's New in 1.0 (Initial Release)

- Stable 1.0 release (2025-12-04): packaging and metadata updates, stability and UI polish.
- Includes unified "Send to AI" workflow with provider selection, and support for local Ollama endpoints.

### Creating Effective Prompts

1. **Clear Objectives**
   - Define specific goals
   - State expected outputs
   - Include context

2. **Appropriate Detail**
   - Include relevant info
   - Avoid excess detail
   - Stay focused

3. **Language Choice**
   - Match target audience
   - Use consistent terminology
   - Consider localization

### Managing Content

1. **Organization**
   - Group related prompts
   - Use consistent naming
   - Track versions

2. **Reusability**
   - Create templates
   - Share common elements
   - Adapt existing prompts

## Troubleshooting

### Common Issues

1. **Access Problems**
   - Verify permissions
   - Check course enrollment
   - Confirm capabilities

2. **Form Issues**
   - Required fields
   - Valid input formats
   - Browser compatibility

3. **Integration Errors**
   - Check settings
   - Verify connections
   - Review logs

### Getting Help

1. **Documentation**
   - Read README
   - Check Moodle docs
   - Review updates

2. **Support**
   - Contact admin
   - Forum posts
   - Issue tracker

## Privacy and Security

### Data Handling

1. **User Data**
   - Input processing
   - Storage location
   - Retention policy

2. **AI Services**
   - Data transmission
   - Provider policies
   - Local vs cloud

### Compliance

1. **GDPR**
   - Data protection
   - User consent
   - Rights management

2. **Institutional**
   - Local policies
   - Data agreements
   - Usage guidelines

## Updates and Maintenance

### Staying Current

1. **Version Checks**
   - Monitor updates
   - Review changes
   - Update when ready

2. **Settings Review**
   - Verify configuration
   - Check integrations
   - Test functionality

### Backup and Recovery

1. **Data Backup**
   - Regular exports
   - Safe storage
   - Recovery testing

2. **Settings Backup**
   - Configuration save
   - Provider settings
   - Custom templates

## Additional Resources

### Documentation

1. **Official Docs**
   - Plugin manual
   - Moodle guides
   - API reference

2. **Community**
   - User forums
   - Support channels
   - Knowledge base

### Training

1. **Tutorials**
   - Getting started
   - Advanced features
   - Best practices

2. **Examples**
   - Sample prompts
   - Use cases
   - Success stories
