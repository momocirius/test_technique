echo "RUNNING APP..."
echo "---------"
docker compose exec app php public/index.php || echo "Error while running app..."

echo "---------"
echo 'DONE.'
