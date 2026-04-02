# Security Policy

## Supported Versions

Security fixes are applied to the latest state of the `main` branch. If you depend on tagged releases, upgrade to the latest compatible release before reporting an issue.

## Reporting a Vulnerability

Do not open public GitHub issues for suspected vulnerabilities.

Report security issues privately by email:

- `memran.dhk@gmail.com`

Include the following when possible:

- A clear description of the issue
- Impact and affected components
- Reproduction steps or proof of concept
- Suggested mitigation, if known

## Response Expectations

The project aims to acknowledge valid reports promptly and coordinate a fix before public disclosure. Timing may vary depending on severity and maintainer availability.

## Scope Notes

Reports are most helpful when they relate directly to this repository, such as:

- Unsafe defaults in bootstrap, middleware, routing, or view integration
- Secrets exposure or insecure environment/config handling
- Injection, traversal, deserialization, or output-escaping risks in framework code
- Dependency or release-process issues that materially affect consumers
