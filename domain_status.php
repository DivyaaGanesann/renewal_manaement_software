<?php
ob_start();
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config.php';
include 'get_counts.php';

$domains = [];
$query = "SELECT 
            id, domain_name, renewal_date, customer_name
          FROM 
            domain_list 
          WHERE 
            domain_name IS NOT NULL AND domain_name != ''
          ORDER BY 
            domain_name ASC";

$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $domains[] = $row;
    }
    mysqli_free_result($result);
}

include 'header.php'; 
include 'navbar.php'; 
?>

<style>
.status-card {
    border-radius: 10px;
    height: 100%;
    position: relative;
    overflow: hidden;
}
.card-checking { background-color: #e9f5ff; border: 2px solid #0d6efd; }
.card-active { border: 2px solid #198754; }
.card-expired { border: 2px solid #dc3545; }
.card-warning { border: 2px solid #ffc107; }

.status-indicator {
    display: flex;
    align-items: center;
    font-weight: 600;
}
.status-dot {
    height: 12px;
    width: 12px;
    border-radius: 50%;
    margin-right: 8px;
    position: relative;
}
.dot-checking { background-color: #0d6efd; }
.dot-active { background-color: #198754; }
.dot-expired { background-color: #dc3545; }
.dot-warning { background-color: #ffc107; }

.status-dot::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    animation: wave 1.5s infinite;
    opacity: 0.7;
    z-index: 0;
}

.dot-checking::before { box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.5); }
.dot-active::before { box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.5); }
.dot-expired::before { box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.5); }
.dot-warning::before { box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.5); }

@keyframes wave {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.7;
    }
    100% {
        transform: translate(-50%, -50%) scale(2.5);
        opacity: 0;
    }
}

.card-domain {
    font-size: 1.15rem;
    font-weight: bold;
    color: #333;
}
.card-customer {
    font-size: 0.95rem;
    color: #555;
    margin-bottom: 5px;
}
</style>

<div class="content-area">
    <div class="card shadow-lg p-4" style="margin:auto;">
        <h4 class="mb-4 text-primary text-center">Domain Status Monitor</h4>

        <div class="row g-4 mt-3"> 
        
        <?php if (empty($domains)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">No domains found in the list that have a domain name specified.</div>
            </div>
        <?php else: ?>
            <?php foreach ($domains as $domain): ?>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card status-card card-checking" id="card-<?= $domain['id'] ?>" data-domain="<?= htmlspecialchars($domain['domain_name']) ?>">
                        <div class="card-body">
                           <a href="<?= htmlspecialchars($domain['domain_name']) ?>" target="_blank" class="text-decoration-none card-domain d-block mb-2">
    <?= htmlspecialchars($domain['domain_name']) ?>
</a>

                            <div class="card-customer">
                                Customer: <?= htmlspecialchars($domain['customer_name']) ?: 'N/A' ?>
                            </div>

                            <div class="status-indicator" id="indicator-<?= $domain['id'] ?>">
                                <div class="status-dot dot-checking"></div>
                                <span class="text-primary">Checking Status...</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    const domainCards = $('.status-card');
    let domainsToCheck = [];

    domainCards.each(function() {
        if ($(this).data('domain')) {
            domainsToCheck.push({
                id: $(this).attr('id').split('-')[1],
                name: $(this).data('domain')
            });
        }
    });

    function checkDomainStatus(domain) {
        const cardId = '#card-' + domain.id;
        const indicatorId = '#indicator-' + domain.id;

        $.ajax({
            url: 'check_domain_status.php',
            method: 'POST',
            data: { domain_name: domain.name },
            dataType: 'json',
            success: function(response) {
                const cardClass = response.cardClass;
                const dotClass = cardClass.replace('card-', 'dot-');
                const statusText = response.operationalStatusText;
                
                $(cardId)
                    .removeClass('card-checking card-active card-expired card-warning')
                    .addClass(cardClass);
                
                $(indicatorId).find('.status-dot')
                    .removeClass('dot-checking dot-active dot-expired dot-warning')
                    .addClass(dotClass);
                
                const textColorClass = (dotClass === 'dot-active') ? 'text-success' : 
                                               (dotClass === 'dot-warning') ? 'text-dark' : 'text-danger';
                                               
                $(indicatorId).find('span')
                    .removeClass('text-primary text-success text-danger text-dark')
                    .addClass(textColorClass)
                    .text(statusText);
            },
            error: function() {
                $(cardId)
                    .removeClass('card-checking')
                    .addClass('card-warning');
                
                $(indicatorId).find('.status-dot')
                    .removeClass('dot-checking')
                    .addClass('dot-warning');
                    
                $(indicatorId).find('span')
                    .removeClass('text-primary')
                    .addClass('text-dark')
                    .text('Check Failed (API Error)');
            }
        });
    }

    if (domainsToCheck.length > 0) {
        console.log(`Starting ${domainsToCheck.length} domain checks in parallel...`);
        domainsToCheck.forEach(domain => {
            checkDomainStatus(domain);
        });
    } else {
        console.log("No domains to check.");
    }
});
</script>
<?php include 'footer.php'; ?>