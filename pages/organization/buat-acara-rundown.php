<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Buat Acara: Rundown</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            margin: 0;
            background: #FAFAFC;
            font-family: Arial, Helvetica, sans-serif;
            color: #0E1128
        }

        .container {
            width: 1152px;
            margin: 0 auto
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 9px
        }

        .logo-text {
            font-size: 22px;
            line-height: 28px;
            font-weight: 700;
            letter-spacing: -0.9px;
            color: #0E1128
        }

        .navbar {
            width: 1440px;
            height: 64px;
            background: #FAFAFC;
            border-bottom: 1px solid #E4E4EE
        }

        .navbar .container {
            height: 63px;
            display: flex;
            align-items: center;
            gap: 32px
        }

        .nav-links {
            flex: 1;
            display: flex;
            gap: 24px;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            color: #4B5170
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 40px;
            padding: 0 16px;
            border: 0;
            border-radius: 8px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            white-space: nowrap;
            cursor: default
        }

        .button-large {
            height: 48px;
            padding: 0 22px;
            font-size: 16px;
            line-height: 24px
        }

        .primary-button {
            background: #4B34DB;
            color: #FFFFFF
        }

        .secondary-button {
            background: #FFFFFF;
            color: #0E1128;
            border: 1px solid #C9CBDC
        }

        .ghost-button {
            background: transparent;
            color: #0E1128
        }

        .outline-light-button {
            background: transparent;
            color: #FFFFFF;
            border: 1px solid #6B6E84
        }

        .white-button {
            background: #FFFFFF;
            color: #0E1128
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 99px;
            font-size: 12px;
            line-height: 12px;
            font-weight: 700
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 3px;
            background: currentColor
        }

        .badge-purple {
            background: #F1F0FF;
            color: #3C29B8
        }

        .badge-success {
            background: #E8F6EC;
            color: #15803D
        }

        .badge-warning {
            background: #FEF3E2;
            color: #B45309
        }

        .badge-info {
            background: #E8F0FE;
            color: #1D4ED8
        }

        .badge-error {
            background: #FDECEC;
            color: #B91C1C
        }

        .badge-neutral {
            background: #F1F1F7;
            color: #4B5170
        }

        .poster {
            position: relative;
            overflow: hidden;
            color: #FFFFFF;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 18px
        }

        .poster-art {
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0
        }

        .poster-kicker,
        .poster-title {
            position: relative
        }

        .poster-kicker {
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            opacity: .85
        }

        .poster-title {
            font-size: 26px;
            line-height: 27px;
            font-weight: 700;
            letter-spacing: -0.5px
        }

        .poster-title-card {
            font-size: 22px;
            line-height: 23px
        }

        .poster-indigo {
            background: #4B34DB
        }

        .poster-ink {
            background: #0E1128
        }

        .poster-cyan {
            background: #DDF4F9;
            color: #0E1128
        }

        .poster-amber {
            background: #F6B94A;
            color: #0E1128
        }

        .art-circle-cyan {
            position: absolute;
            right: -50px;
            top: -50px;
            width: 210px;
            height: 210px;
            border-radius: 105px;
            background: #5EC8E5
        }

        .art-ring-dark {
            position: absolute;
            right: 40px;
            top: 60px;
            width: 90px;
            height: 90px;
            border-radius: 45px;
            border: 14px solid #1C1458
        }

        .art-stripes {
            position: absolute;
            left: 102px;
            top: 0;
            width: 168px;
            height: 109px;
            overflow: hidden
        }

        .poster-stripes {
            display: block
        }

        .art-circle-indigo {
            position: absolute;
            right: -30px;
            top: -30px;
            width: 150px;
            height: 150px;
            border-radius: 75px;
            background: #4B34DB
        }

        .art-square-ink {
            position: absolute;
            right: 24px;
            top: 96px;
            width: 70px;
            height: 70px;
            background: #0E1128
        }

        .art-circle-ink {
            position: absolute;
            right: -70px;
            top: -20px;
            width: 220px;
            height: 220px;
            border-radius: 110px;
            background: #0E1128
        }

        .hero {
            width: 1440px;
            height: 640px;
            padding-top: 72px
        }

        .hero .container {
            display: flex;
            gap: 56px
        }

        .hero-copy {
            width: 592px;
            display: flex;
            flex-direction: column
        }

        .hero-badge-row {
            height: 22px;
            margin-bottom: 36px
        }

        .hero-title {
            font-size: 64px;
            line-height: 67px;
            font-weight: 700;
            letter-spacing: -2.2px;
            color: #0E1128;
            white-space: nowrap
        }

        .hero-title-accent {
            color: #4B34DB
        }

        .hero-lead {
            margin-top: 20px;
            font-size: 18px;
            line-height: 27px;
            color: #4B5170
        }

        .search-bar {
            margin-top: 32px;
            width: 592px;
            height: 64px;
            padding: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(14, 17, 40, .08)
        }

        .search-field {
            width: 276px;
            height: 48px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            font-size: 15px;
            color: #5F6483
        }

        .search-select {
            width: 150px;
            height: 48px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 1px solid #E4E4EE;
            font-size: 15px;
            color: #0E1128
        }

        .popular-row {
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            line-height: 21px;
            color: #5F6483
        }

        .chip {
            padding: 6px 12px;
            border-radius: 99px;
            background: #FFFFFF;
            box-shadow: inset 0 0 0 1px #E4E4EE;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            color: #4B5170
        }

        .hero-visual {
            width: 504px;
            display: flex;
            align-items: center;
            justify-content: flex-end
        }

        .ticket {
            width: 400px;
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(14, 17, 40, .14);
            transform: rotate(2deg)
        }

        .ticket .poster {
            border-radius: 16px 16px 0 0
        }

        .ticket-stub {
            position: relative;
            border-top: 2px dashed #E4E4EE;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px
        }

        .ticket-notch-left,
        .ticket-notch-right {
            position: absolute;
            top: -11px;
            width: 20px;
            height: 20px;
            border-radius: 10px;
            background: #FAFAFC
        }

        .ticket-notch-left {
            left: -10px
        }

        .ticket-notch-right {
            right: -10px
        }

        .ticket-info {
            width: 260px
        }

        .ticket-title {
            margin: 10px 0 4px;
            font-size: 16px;
            line-height: 22px;
            font-weight: 700
        }

        .ticket-meta {
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .ticket-id {
            margin-top: 8px;
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: .7px;
            color: #5F6483
        }

        .section {
            width: 1440px;
            padding: 56px 0
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 24px;
            height: 40px
        }

        .section-title {
            font-size: 32px;
            line-height: 37px;
            font-weight: 700;
            letter-spacing: -0.6px;
            color: #0E1128
        }

        .tabs {
            display: flex;
            gap: 24px;
            border-bottom: 1px solid #E4E4EE;
            margin-bottom: 24px;
            height: 32px
        }

        .tab {
            padding-bottom: 10px;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            color: #5F6483
        }

        .tab-active {
            color: #0E1128;
            border-bottom: 2px solid #4B34DB
        }

        .event-grid {
            display: flex;
            gap: 24px
        }

        .event-card {
            width: 270px;
            height: 392px;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(14, 17, 40, .06), 0 1px 3px rgba(14, 17, 40, .06);
            overflow: hidden;
            display: flex;
            flex-direction: column
        }

        .event-card-body {
            flex: 1;
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px
        }

        .event-card-title {
            font-size: 16px;
            line-height: 22px;
            font-weight: 700
        }

        .event-card-meta {
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .event-card-footer {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #F1F1F7;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            line-height: 21px
        }

        .event-card-price {
            font-size: 16px;
            line-height: 24px
        }

        .price-free {
            color: #15803D
        }

        .event-card-left {
            color: #5F6483
        }

        .steps {
            display: flex;
            gap: 32px
        }

        .step {
            width: 362px
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #F1F0FF;
            color: #3C29B8;
            font-size: 16px;
            line-height: 32px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 12px
        }

        .step-title {
            font-size: 18px;
            line-height: 27px;
            font-weight: 700;
            margin-bottom: 6px
        }

        .step-text {
            font-size: 16px;
            line-height: 24px;
            color: #4B5170
        }

        .organizer-card {
            width: 1152px;
            height: 428px;
            padding: 48px;
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(14, 17, 40, .06), 0 1px 3px rgba(14, 17, 40, .06);
            display: flex;
            gap: 48px;
            align-items: center
        }

        .organizer-copy {
            width: 504px
        }

        .organizer-title {
            margin-top: 16px;
            font-size: 32px;
            line-height: 37px;
            font-weight: 700;
            letter-spacing: -0.6px
        }

        .feature-list {
            list-style: none;
            margin: 24px 0 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 16px;
            line-height: 24px;
            color: #4B5170
        }

        .feature-item {
            display: flex;
            gap: 10px
        }

        .feature-dot {
            flex: none;
            width: 6px;
            height: 6px;
            border-radius: 3px;
            background: #0E7490;
            margin-top: 9px
        }

        .approval-panel {
            width: 504px;
            background: #F1F1F7;
            border-radius: 12px;
            padding: 20px
        }

        .panel-label {
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #5F6483
        }

        .status-row {
            display: flex;
            gap: 8px;
            margin: 14px 0 18px
        }

        .event-row {
            margin-top: 8px;
            padding: 12px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            background: #FFFFFF;
            border-radius: 8px;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700
        }

        .panel-note {
            margin: 8px 2px 0;
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .cta {
            width: 1152px;
            height: 186px;
            padding: 0 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0E1128;
            border-radius: 16px
        }

        .cta-title {
            width: 560px;
            font-size: 32px;
            line-height: 37px;
            font-weight: 700;
            letter-spacing: -0.6px;
            color: #FFFFFF
        }

        .cta-actions {
            display: flex;
            gap: 12px
        }

        .footer {
            width: 1440px;
            height: 147px;
            padding-top: 48px
        }

        .footer .container {
            display: flex;
            justify-content: space-between
        }

        .footer-text {
            margin-top: 8px;
            font-size: 14px;
            line-height: 21px;
            color: #5F6483
        }

        .footer-links {
            display: flex;
            gap: 24px;
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .frame {
            background: #FAFAFC;
            position: relative;
            overflow: hidden
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 18px;
            background: #E3E1FF;
            color: #3C29B8;
            font-size: 13px;
            line-height: 36px;
            font-weight: 700;
            text-align: center
        }

        .avatar-large {
            width: 64px;
            height: 64px;
            border-radius: 32px;
            font-size: 20px;
            line-height: 64px
        }

        .small-button {
            height: 32px;
            padding: 0 12px
        }

        .danger-button {
            background: #B91C1C;
            color: #FFFFFF
        }

        .danger-outline-button {
            background: #FFFFFF;
            color: #B91C1C;
            border: 1px solid #B91C1C
        }

        .disabled-button {
            background: #4B34DB;
            color: #FFFFFF;
            opacity: .45
        }

        .full-width {
            width: 100%
        }

        .page-body {
            width: 1152px;
            margin: 0 auto
        }

        .page-title {
            font-size: 32px;
            line-height: 37px;
            font-weight: 700;
            letter-spacing: -0.6px
        }

        .page-subtitle {
            margin-top: 6px;
            font-size: 16px;
            line-height: 24px;
            color: #4B5170
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 24px
        }

        .card {
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(14, 17, 40, .06), 0 1px 3px rgba(14, 17, 40, .06);
            padding: 24px
        }

        .card-raised {
            box-shadow: 0 4px 12px rgba(14, 17, 40, .08)
        }

        .card-title {
            font-size: 22px;
            line-height: 30px;
            font-weight: 700;
            margin-bottom: 16px
        }

        .stack {
            display: flex;
            flex-direction: column
        }

        .gap-8 {
            gap: 8px
        }

        .gap-10 {
            gap: 10px
        }

        .gap-12 {
            gap: 12px
        }

        .gap-16 {
            gap: 16px
        }

        .gap-24 {
            gap: 24px
        }

        .row {
            display: flex;
            align-items: center;
            gap: 12px
        }

        .row-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px
        }

        .two-fields {
            display: flex;
            gap: 16px
        }

        .two-fields>* {
            flex: 1
        }

        .two-col {
            display: flex;
            gap: 32px;
            align-items: flex-start
        }

        .col-main {
            width: 740px
        }

        .col-side {
            width: 380px
        }

        .field-label {
            display: block;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #0E1128
        }

        .input {
            height: 40px;
            border: 1px solid #C9CBDC;
            border-radius: 8px;
            padding: 0 12px;
            background: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 15px;
            color: #0E1128
        }

        .input-placeholder {
            color: #5F6483
        }

        .input-error {
            border-color: #B91C1C;
            box-shadow: 0 0 0 3px #FDECEC
        }

        .input-area {
            height: 84px;
            align-items: flex-start;
            padding-top: 10px
        }

        .help {
            margin-top: 6px;
            font-size: 12px;
            line-height: 18px;
            color: #5F6483
        }

        .help-error {
            color: #B91C1C;
            font-weight: 700
        }

        .alert {
            display: flex;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 21px
        }

        .alert-success {
            background: #E8F6EC;
            color: #14532D
        }

        .alert-error {
            background: #FDECEC;
            color: #7F1D1D
        }

        .alert-info {
            background: #E8F0FE;
            color: #1E3A8A
        }

        .alert-warning {
            background: #FEF3E2;
            color: #78350F
        }

        .check-row {
            display: flex;
            gap: 10px;
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .checkbox {
            flex: none;
            width: 16px;
            height: 16px;
            margin-top: 3px;
            border: 1.5px solid #8A90AB;
            border-radius: 4px;
            background: #FFFFFF
        }

        .link-text {
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            color: #4B34DB
        }

        .muted {
            color: #5F6483
        }

        .slate {
            color: #4B5170
        }

        .breadcrumb {
            font-size: 14px;
            line-height: 21px;
            color: #5F6483;
            margin-bottom: 16px
        }

        .breadcrumb span:first-child {
            color: #4B5170
        }

        .meta-row {
            display: flex;
            gap: 24px;
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .rundown-row {
            display: flex;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #F1F1F7;
            font-size: 14px;
            line-height: 21px
        }

        .rundown-time {
            width: 56px;
            flex: none;
            font-weight: 700;
            color: #3C29B8
        }

        .option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #FFFFFF;
            box-shadow: inset 0 0 0 1px #E4E4EE;
            font-size: 14px;
            line-height: 21px
        }

        .option-selected {
            box-shadow: inset 0 0 0 2px #4B34DB;
            background: #F1F0FF
        }

        .option-disabled {
            opacity: .55
        }

        .option-price {
            font-size: 16px;
            font-weight: 700
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #E4E4EE;
            padding-top: 12px;
            font-size: 14px;
            color: #4B5170
        }

        .total-value {
            font-size: 22px;
            line-height: 30px;
            font-weight: 700;
            color: #0E1128
        }

        .stepper {
            display: flex;
            gap: 24px;
            margin-bottom: 28px;
            font-size: 14px;
            line-height: 24px;
            font-weight: 700;
            color: #5F6483
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 8px
        }

        .step-dot {
            width: 24px;
            height: 24px;
            border-radius: 12px;
            background: #F1F1F7;
            font-size: 12px;
            line-height: 24px;
            text-align: center
        }

        .step-active {
            color: #0E1128
        }

        .step-active .step-dot {
            background: #4B34DB;
            color: #FFFFFF
        }

        .step-done .step-dot {
            background: #E8F6EC;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .summary-total {
            font-size: 16px;
            font-weight: 700;
            color: #0E1128
        }

        .success-icon {
            width: 56px;
            height: 56px;
            border-radius: 28px;
            background: #E8F6EC;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto
        }

        .definition {
            display: flex;
            flex-wrap: wrap;
            gap: 16px 12px
        }

        .definition div {
            width: calc(50% - 6px)
        }

        .definition .wide {
            width: 100%
        }

        .definition dt {
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: .7px;
            color: #5F6483
        }

        .definition dd {
            margin: 2px 0 0;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700
        }

        .ticket-row {
            display: flex;
            gap: 20px;
            align-items: center;
            padding: 12px;
            margin-bottom: 12px;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(14, 17, 40, .06), 0 1px 3px rgba(14, 17, 40, .06)
        }

        .ticket-row .poster {
            width: 132px;
            height: 100px;
            flex: none;
            border-radius: 8px;
            padding: 10px
        }

        .ticket-row .poster-title {
            font-size: 16px;
            line-height: 17px
        }

        .ticket-row-body {
            flex: 1
        }

        .ticket-row-title {
            margin: 6px 0 2px;
            font-size: 16px;
            line-height: 22px;
            font-weight: 700
        }

        .ticket-row-meta {
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .is-dimmed {
            filter: grayscale(75%)
        }

        .group-label {
            margin: 32px 0 12px;
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #5F6483
        }

        .tabs-wide {
            margin-bottom: 24px
        }

        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: inset 0 0 0 1px #E4E4EE
        }

        .side-nav {
            width: 240px;
            flex: none
        }

        .side-link {
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            color: #4B5170
        }

        .side-link-active {
            background: #F1F0FF;
            color: #3C29B8
        }

        .state-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 40px 24px
        }

        .state-icon {
            width: 48px;
            height: 48px;
            border-radius: 24px;
            background: #F1F0FF;
            color: #3C29B8;
            font-size: 18px;
            line-height: 48px;
            font-weight: 700;
            text-align: center
        }

        .state-title {
            margin: 12px 0 4px;
            font-size: 18px;
            line-height: 27px;
            font-weight: 700
        }

        .state-text {
            margin-bottom: 16px;
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .skeleton {
            background: #F1F1F7;
            border-radius: 8px
        }

        .dialog {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(14, 17, 40, .14);
            padding: 24px;
            width: 420px
        }

        .dialog-title {
            font-size: 22px;
            line-height: 30px;
            font-weight: 700
        }

        .dialog-text {
            margin: 8px 0 16px;
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .search-wide {
            width: 1152px
        }

        .search-wide .search-field {
            flex: 1;
            width: auto
        }

        .chips-row {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 16px
        }

        .chip-active {
            background: #0E1128;
            color: #FFFFFF;
            box-shadow: none
        }

        .footer-bar {
            width: 100%
        }

        .badge-cyan {
            background: #DDF4F9;
            color: #0E7490
        }

        .app {
            display: flex;
            align-items: stretch
        }

        .sidebar {
            width: 248px;
            flex: none;
            background: #FFFFFF;
            border-right: 1px solid #E4E4EE;
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px
        }

        .sidebar .logo {
            padding: 4px 12px 20px
        }

        .side-item {
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            color: #4B5170
        }

        .side-item-active {
            background: #F1F0FF;
            color: #3C29B8
        }

        .side-user {
            margin-top: auto;
            padding: 12px;
            border-top: 1px solid #E4E4EE;
            font-size: 12px;
            line-height: 18px;
            color: #5F6483
        }

        .side-user b {
            display: block;
            font-size: 14px;
            line-height: 21px;
            color: #0E1128
        }

        .main {
            width: 1192px;
            flex: none;
            padding: 24px 32px 56px
        }

        .crumb {
            font-size: 14px;
            line-height: 21px;
            color: #5F6483
        }

        .head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin: 8px 0 24px
        }

        .head-actions {
            display: flex;
            gap: 8px;
            align-items: center
        }

        .metrics {
            display: flex;
            gap: 16px;
            margin-bottom: 24px
        }

        .metric {
            flex: 1;
            background: #FFFFFF;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 2px rgba(14, 17, 40, .06), 0 1px 3px rgba(14, 17, 40, .06)
        }

        .metric-label {
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: .7px;
            color: #5F6483;
            text-transform: none
        }

        .metric-value {
            margin: 4px 0;
            font-size: 28px;
            line-height: 36px;
            font-weight: 700;
            letter-spacing: -0.5px
        }

        .metric-note {
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .cols {
            display: flex;
            gap: 24px;
            align-items: flex-start
        }

        .col-wide {
            width: 680px;
            flex: none
        }

        .col-narrow {
            width: 424px;
            flex: none
        }

        .tbl-card {
            background: #FFFFFF;
            border-radius: 12px;
            padding: 8px 16px;
            box-shadow: 0 1px 2px rgba(14, 17, 40, .06), 0 1px 3px rgba(14, 17, 40, .06)
        }

        .tbl-head,
        .tbl-row {
            display: flex;
            align-items: center
        }

        .tbl-head {
            padding: 10px 0;
            border-bottom: 1px solid #E4E4EE;
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: .7px;
            color: #5F6483
        }

        .tbl-row {
            padding: 14px 0;
            border-bottom: 1px solid #F1F1F7
        }

        .tbl-cell {
            flex: none;
            padding: 0 12px;
            font-size: 14px;
            line-height: 21px
        }

        .tbl-right {
            text-align: right;
            display: flex;
            justify-content: flex-end;
            gap: 4px
        }

        .cell-sub {
            color: #4B5170
        }

        .progress {
            height: 6px;
            background: #F1F1F7;
            border-radius: 99px;
            overflow: hidden
        }

        .progress-fill {
            height: 6px;
            background: #4B34DB
        }

        .progress-over {
            background: #B91C1C
        }

        .feed-item {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #F1F1F7;
            font-size: 14px;
            line-height: 21px
        }

        .feed-dot {
            flex: none;
            width: 8px;
            height: 8px;
            border-radius: 4px;
            background: #5B45F0;
            margin-top: 7px
        }

        .feed-dot-warning {
            background: #B45309
        }

        .feed-dot-muted {
            background: #5F6483
        }

        .feed-time {
            font-size: 12px;
            line-height: 18px;
            color: #5F6483
        }

        .dropzone {
            border: 2px dashed #C9CBDC;
            border-radius: 12px;
            padding: 28px;
            text-align: center;
            background: #F1F1F7;
            color: #4B5170;
            font-size: 14px;
            line-height: 21px
        }

        .run-row {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #FFFFFF;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 2px rgba(14, 17, 40, .06), 0 1px 3px rgba(14, 17, 40, .06);
            font-size: 14px;
            line-height: 21px
        }

        .run-time {
            width: 48px;
            flex: none;
            font-weight: 700;
            color: #3C29B8
        }

        .run-body {
            flex: 1
        }

        .run-body p {
            margin-top: 2px;
            color: #4B5170
        }

        .timeline {
            border-left: 2px solid #E4E4EE;
            margin-left: 6px;
            padding-left: 20px
        }

        .timeline-item {
            position: relative;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 21px
        }

        .timeline-dot {
            position: absolute;
            left: -27px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 5px;
            background: #5B45F0;
            box-shadow: 0 0 0 3px #FFFFFF
        }

        .timeline-dot-error {
            background: #B91C1C
        }

        .wizard-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #E4E4EE;
            padding-top: 12px;
            margin-top: 24px
        }

        .chip-row {
            display: flex;
            gap: 8px;
            margin-bottom: 16px
        }

        .filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 16px
        }

        .filter-bar .input {
            flex: none
        }

        .legend-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            line-height: 21px;
            color: #0E1128;
            margin-bottom: 6px
        }

        .count-pill {
            margin-left: 8px;
            display: inline-block;
            padding: 2px 7px;
            border-radius: 99px;
            background: #FEF3E2;
            color: #B45309;
            font-size: 12px;
            line-height: 14px;
            font-weight: 700
        }

        .side-label {
            padding: 0 12px 8px;
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #5F6483
        }

        .stack-bar {
            display: flex;
            height: 14px;
            border-radius: 99px;
            overflow: hidden;
            gap: 2px
        }

        .legend-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
            margin-top: 16px;
            font-size: 14px;
            line-height: 21px
        }

        .legend-item {
            width: calc(50% - 8px);
            display: flex;
            justify-content: space-between;
            color: #4B5170
        }

        .legend-swatch {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 3px;
            margin-right: 8px
        }

        .spec {
            padding: 56px 64px 72px
        }

        .spec-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            align-items: flex-start
        }

        .spec-cell {
            width: 620px
        }

        .spec-label {
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #5F6483;
            margin-bottom: 12px
        }

        .reason-box {
            background: #FDECEC;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 14px;
            line-height: 21px;
            color: #7F1D1D
        }
    </style>
</head>

<body>
    <div class="frame" style="width:1440px;height:795px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path
                            d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                            fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                            fill="none" />
                    </svg><span class="logo-text">tixora</span></div><span class="side-item">Dasbor</span><span
                    class="side-item">Acara saya</span><span class="side-item side-item-active">Buat acara</span><span
                    class="side-item">Pengaturan</span><span class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / Buat acara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Buat acara baru</h1>
                        <p class="page-subtitle">Langkah 3 dari 4 · Draf tersimpan 10 Okt, 09.20</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Pratinjau</span></div>
                </div>
                <div class="stepper"><span class="step-item step-done"><span class="step-dot"><svg width="12"
                                height="12" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>Informasi &amp; jadwal</span><span class="step-item step-done"><span
                            class="step-dot"><svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>Tiket &amp; kapasitas</span><span class="step-item step-active"><span
                            class="step-dot">3</span>Rundown</span><span class="step-item "><span
                            class="step-dot">4</span>Tinjau &amp; ajukan</span></div>
                <div class="cols">
                    <div style="width:680px">
                        <div class="run-row"><svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true"
                                fill="#8A90AB">
                                <circle cx="4" cy="4" r="1.5" />
                                <circle cx="10" cy="4" r="1.5" />
                                <circle cx="4" cy="9" r="1.5" />
                                <circle cx="10" cy="9" r="1.5" />
                                <circle cx="4" cy="14" r="1.5" />
                                <circle cx="10" cy="14" r="1.5" />
                            </svg><span class="run-time">07.30</span>
                            <div class="run-body"><b>Registrasi ulang</b>
                                <p>Pemindaian kode QR peserta · PIC: Putri Ayuningtyas</p>
                            </div><span class="button ghost-button small-button" style="padding:0 8px"><svg width="14"
                                    height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button" style="padding:0 8px"><svg
                                    width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button">Edit</span><span
                                class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                        </div>
                        <div class="run-row"><svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true"
                                fill="#8A90AB">
                                <circle cx="4" cy="4" r="1.5" />
                                <circle cx="10" cy="4" r="1.5" />
                                <circle cx="4" cy="9" r="1.5" />
                                <circle cx="10" cy="9" r="1.5" />
                                <circle cx="4" cy="14" r="1.5" />
                                <circle cx="10" cy="14" r="1.5" />
                            </svg><span class="run-time">08.30</span>
                            <div class="run-body"><b>Pembukaan</b>
                                <p>Sambutan ketua pelaksana dan dekan · PIC: Dimas Prakoso</p>
                            </div><span class="button ghost-button small-button" style="padding:0 8px"><svg width="14"
                                    height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button" style="padding:0 8px"><svg
                                    width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button">Edit</span><span
                                class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                        </div>
                        <div class="run-row"><svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true"
                                fill="#8A90AB">
                                <circle cx="4" cy="4" r="1.5" />
                                <circle cx="10" cy="4" r="1.5" />
                                <circle cx="4" cy="9" r="1.5" />
                                <circle cx="10" cy="9" r="1.5" />
                                <circle cx="4" cy="14" r="1.5" />
                                <circle cx="10" cy="14" r="1.5" />
                            </svg><span class="run-time">09.00</span>
                            <div class="run-body"><b>Keynote: Arah AI di Industri Nasional</b>
                                <p>Dr. Rina Kusumawati, M.Kom. · PIC: Nabila Safitri</p>
                            </div><span class="button ghost-button small-button" style="padding:0 8px"><svg width="14"
                                    height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button" style="padding:0 8px"><svg
                                    width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button">Edit</span><span
                                class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                        </div>
                        <div class="run-row"><svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true"
                                fill="#8A90AB">
                                <circle cx="4" cy="4" r="1.5" />
                                <circle cx="10" cy="4" r="1.5" />
                                <circle cx="4" cy="9" r="1.5" />
                                <circle cx="10" cy="9" r="1.5" />
                                <circle cx="4" cy="14" r="1.5" />
                                <circle cx="10" cy="14" r="1.5" />
                            </svg><span class="run-time">11.00</span>
                            <div class="run-body"><b>Diskusi panel: Karier di Era Otomasi</b>
                                <p>Bagas Pratama dan Dewi Anggraeni · PIC: Fajar Nugroho</p>
                            </div><span class="button ghost-button small-button" style="padding:0 8px"><svg width="14"
                                    height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button" style="padding:0 8px"><svg
                                    width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button">Edit</span><span
                                class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                        </div>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Kegiatan baru</h2>
                        <div class="stack gap-16">
                            <div class="two-fields">
                                <div><label class="field-label">Mulai</label>
                                    <div class="input"><span class="">12.00</span></div>
                                </div>
                                <div><label class="field-label">Selesai</label>
                                    <div class="input"><span class="">13.00</span></div>
                                </div>
                            </div>
                            <div><label class="field-label">Kegiatan</label>
                                <div class="input"><span class="input-placeholder">Nama kegiatan</span></div>
                            </div>
                            <div><label class="field-label">Deskripsi</label>
                                <div class="input"><span class="input-placeholder">Keterangan singkat</span></div>
                            </div>
                            <div><label class="field-label">Penanggung jawab</label>
                                <div class="input"><span class="input-placeholder">Pilih anggota panitia</span><svg
                                        width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg></div>
                            </div><span class="button primary-button">Simpan kegiatan</span>
                        </div>
                    </div>
                </div>
                <div class="wizard-footer" style="width:1128px;box-sizing:border-box"><span
                        class="button ghost-button">Simpan draf</span>
                    <div class="row"><span class="button secondary-button">Kembali</span><span
                            class="button primary-button">Lanjut: Tinjau &amp; ajukan</span></div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>