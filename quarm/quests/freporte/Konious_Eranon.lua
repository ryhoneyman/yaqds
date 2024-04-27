function event_say(e)
	if(e.message:findi("hail")) then
		e.self:Say("Greetings!  I am the mighty Konious Eranon, Master Enchanter, devoted follower of Innoruuk, and loyal assistant to the all-powerful Nexvok.");
	end
	local enchant_bars_lib = require("self_found_enchant_bars");
	enchant_bars_lib.check_bars_quest_dialogue(e.self, e.other, e.message);
end

function event_trade(e)
	local item_lib = require("items");

	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 18856})) then -- A tattered Note
		e.self:Say("Hey, Nex, we got another sucker.. er.. volunteer, that is, to help us out around here. Here ya go friend, put this on and let's whip you into shape.");
		e.other:Faction(e.self,271,100,0); -- Dismal Rage
		e.other:Faction(e.self,281,-15,0); -- Knights of Truth
		e.other:Faction(e.self,296,20,0); -- Opal Dark Briar
		e.other:QuestReward(e.self,0,0,0,0,13566,20); -- Blood Spotted Robe*
	end

	local enchant_bars_lib = require("self_found_enchant_bars");
	enchant_bars_lib.check_for_bars_to_enchant(item_lib, e.self, e.other, e.trade);

	item_lib.return_items(e.self, e.other, e.trade)
end

-- END of FILE Zone:freporte -- Konious_Eranon



